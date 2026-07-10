<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CatchResource;
use App\Models\Activity;
use App\Models\CatchRecord;
use App\Models\Follow;
use App\Models\Permit;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class CabinetController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $catches = CatchRecord::query()
            ->where('user_id', $user->id)
            ->with('lake:id,name,slug')
            ->latest('caught_at')
            ->get();

        $catchesCount = $catches->count();
        $lakesVisited = $catches->pluck('lake_id')->unique()->count();
        $biggestCatch = $catches->sortByDesc('weight')->first();
        $photosCount = $catches->whereNotNull('photo')->count();
        $followersCount = Follow::where('following_id', $user->id)->count();
        $nightCatchesCount = $this->countNightCatches($catches);

        $permits = Permit::query()
            ->where('user_id', $user->id)
            ->with('lake:id,name,slug')
            ->where('status', 'active')
            ->where('expires_at', '>=', now())
            ->orderBy('expires_at')
            ->limit(3)
            ->get()
            ->map(fn (Permit $permit) => [
                'id' => $permit->id,
                'lake_name' => $permit->lake->name,
                'lake_slug' => $permit->lake->slug,
                'duration_days' => $permit->duration_days,
                'duration_label' => $permit->duration_days.'-денний дозвіл',
                'expires_at' => $permit->expires_at->format('d.m.Y'),
                'days_left' => (int) now()->diffInDays($permit->expires_at, false),
                'days_total' => $permit->duration_days,
                'status' => $permit->status,
            ]);

        return response()->json([
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'location' => $user->location,
                'bio' => $user->bio,
                'avatar_url' => $user->avatar_url,
                'badge' => $user->badge,
            ],
            'stats' => [
                'catches_count' => $catchesCount,
                'lakes_visited' => $lakesVisited,
                'biggest_fish_weight' => $biggestCatch?->weight,
                'biggest_fish_name' => $biggestCatch?->fish_name,
                'followers_count' => $followersCount,
                'total_likes' => PostLike::whereIn('catch_id', $catches->pluck('id'))->count(),
                'ranking' => 0,
            ],
            'permits' => $permits,
            'achievements' => $this->buildAchievements($catchesCount, $lakesVisited, $photosCount, $biggestCatch, $followersCount, $nightCatchesCount),
            'recent_catches' => CatchResource::collection($catches->take(4)),
            'activity' => $this->buildActivity($user),
        ]);
    }

    public function friends(Request $request): JsonResponse
    {
        $user = $request->user();

        $following = Follow::where('follower_id', $user->id)
            ->with('following:id,name,avatar_url,badge')
            ->get()
            ->pluck('following')
            ->filter();

        $followers = Follow::where('following_id', $user->id)
            ->with('follower:id,name,avatar_url,badge')
            ->get()
            ->pluck('follower')
            ->filter();

        $followingIds = $following->pluck('id')->all();

        $shape = fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'avatar_url' => $u->avatar_url,
            'badge' => $u->badge,
        ];

        return response()->json([
            'following' => $following->map($shape)->values(),
            'followers' => $followers->map(fn (User $u) => $shape($u) + [
                // Lets the UI mark people who follow you back
                'is_following' => in_array($u->id, $followingIds, true),
            ])->values(),
        ]);
    }

    public function achievements(Request $request): JsonResponse
    {
        $user = $request->user();

        $catches = CatchRecord::where('user_id', $user->id)->get();
        $catchesCount = $catches->count();
        $lakesVisited = $catches->pluck('lake_id')->unique()->count();
        $photosCount = $catches->whereNotNull('photo')->count();
        $biggestCatch = $catches->sortByDesc('weight')->first();
        $followersCount = Follow::where('following_id', $user->id)->count();
        $nightCatchesCount = $this->countNightCatches($catches);

        $achievements = $this->buildAchievements($catchesCount, $lakesVisited, $photosCount, $biggestCatch, $followersCount, $nightCatchesCount);

        return response()->json([
            'achievements' => $achievements,
            'earned_count' => count(array_filter($achievements, fn ($a) => $a['earned'])),
            'total_count' => count($achievements),
        ]);
    }

    private function countNightCatches($catches): int
    {
        return $catches->filter(
            fn ($c) => $c->caught_at && ($c->caught_at->hour >= 22 || $c->caught_at->hour < 6)
        )->count();
    }

    private function buildAchievements(
        int $catchesCount,
        int $lakesVisited,
        int $photosCount,
        ?CatchRecord $biggestCatch,
        int $followersCount = 0,
        int $nightCatchesCount = 0
    ): array {
        $biggestWeight = (float) ($biggestCatch?->weight ?? 0);

        return [
            [
                'id' => 'first_catch',
                'title' => 'Перший улов',
                'description' => 'Додай свій перший улов',
                'icon' => 'fish',
                'progress' => min(1, $catchesCount),
                'max' => 1,
                'earned' => $catchesCount >= 1,
            ],
            [
                'id' => 'catches_10',
                'title' => 'Досвідчений рибалка',
                'description' => 'Зловити 10 риб',
                'icon' => 'star',
                'progress' => min($catchesCount, 10),
                'max' => 10,
                'earned' => $catchesCount >= 10,
            ],
            [
                'id' => 'catches_100',
                'title' => 'Легенда',
                'description' => 'Зловити 100 риб',
                'icon' => 'trophy',
                'progress' => min($catchesCount, 100),
                'max' => 100,
                'earned' => $catchesCount >= 100,
            ],
            [
                'id' => 'big_fish',
                'title' => 'Велика здобич',
                'description' => 'Зловити рибу вагою 10+ кг',
                'icon' => 'fish',
                'progress' => min((int) $biggestWeight, 10),
                'max' => 10,
                'earned' => $biggestWeight >= 10,
            ],
            [
                'id' => 'explorer_5',
                'title' => 'Дослідник',
                'description' => 'Відвідати 5 різних озер',
                'icon' => 'lake',
                'progress' => min($lakesVisited, 5),
                'max' => 5,
                'earned' => $lakesVisited >= 5,
            ],
            [
                'id' => 'explorer_25',
                'title' => 'Мандрівник',
                'description' => 'Відвідати 25 різних озер',
                'icon' => 'map',
                'progress' => min($lakesVisited, 25),
                'max' => 25,
                'earned' => $lakesVisited >= 25,
            ],
            [
                'id' => 'photographer_10',
                'title' => 'Фотограф',
                'description' => 'Додати фото до 10 уловів',
                'icon' => 'camera',
                'progress' => min($photosCount, 10),
                'max' => 10,
                'earned' => $photosCount >= 10,
            ],
            [
                'id' => 'photographer_50',
                'title' => 'Фотомайстер',
                'description' => 'Додати фото до 50 уловів',
                'icon' => 'camera',
                'progress' => min($photosCount, 50),
                'max' => 50,
                'earned' => $photosCount >= 50,
            ],
            [
                'id' => 'night_fisher',
                'title' => 'Нічний рибалка',
                'description' => 'Зловити 10 риб вночі (22:00–06:00)',
                'icon' => 'moon',
                'progress' => min($nightCatchesCount, 10),
                'max' => 10,
                'earned' => $nightCatchesCount >= 10,
            ],
            [
                'id' => 'popular',
                'title' => 'Популярний',
                'description' => 'Отримати 10 підписників',
                'icon' => 'people',
                'progress' => min($followersCount, 10),
                'max' => 10,
                'earned' => $followersCount >= 10,
            ],
        ];
    }

    private function buildActivity(User $user): array
    {
        return Activity::where('user_id', $user->id)
            ->latest()
            ->limit(15)
            ->get()
            ->map(function (Activity $activity) {
                $d = $activity->data;

                return match ($activity->type) {
                    'catch' => [
                        'type' => 'catch',
                        'data' => [
                            'fish_name' => $d['fish_name'] ?? '',
                            'weight'    => $d['weight'] ?? '',
                            'lake_name' => $d['lake_name'] ?? '',
                        ],
                        'created_at' => $activity->created_at->toISOString(),
                    ],
                    'following' => [
                        'type' => 'following',
                        'data' => [
                            'name' => $d['following_name'] ?? '',
                            'id'   => $d['following_id'] ?? null,
                        ],
                        'created_at' => $activity->created_at->toISOString(),
                    ],
                    'follower' => [
                        'type' => 'follower',
                        'data' => [
                            'name' => $d['follower_name'] ?? '',
                            'id'   => $d['follower_id'] ?? null,
                        ],
                        'author_name' => $d['follower_name'] ?? '',
                        'created_at' => $activity->created_at->toISOString(),
                    ],
                    default => null,
                };
            })
            ->filter()
            ->values()
            ->toArray();
    }
}
