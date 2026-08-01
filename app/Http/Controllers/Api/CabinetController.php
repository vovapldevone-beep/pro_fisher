<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\BuildsAchievements;
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
    use BuildsAchievements;

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
                'username' => $user->username,
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
            ],
            'permits' => $permits,
            'achievements' => $this->buildAchievements($catchesCount, $lakesVisited, $photosCount, $biggestCatch, $followersCount, $nightCatchesCount),
            'recent_catches' => CatchResource::collection($catches->take(4)),
            'activity' => $this->buildActivity($user),
        ]);
    }

    /**
     * One page of either tab of the friends modal.
     *
     * Paginated rather than returned whole: a popular account can have thousands
     * of followers, and the modal only ever shows a screenful at a time.
     * `counts` is sent on every page so the tab labels stay correct while the
     * list is still being scrolled in.
     */
    public function friends(Request $request): JsonResponse
    {
        $user = $request->user();
        $tab = $request->query('tab') === 'followers' ? 'followers' : 'following';

        // following → the people I follow; followers → the people who follow me
        $theirColumn = $tab === 'following' ? 'following_id' : 'follower_id';
        $myColumn = $tab === 'following' ? 'follower_id' : 'following_id';

        $page = User::query()
            ->join('follows', 'follows.'.$theirColumn, '=', 'users.id')
            ->where('follows.'.$myColumn, $user->id)
            // Newest connection first, and a stable order for pagination
            ->orderByDesc('follows.id')
            ->paginate(15, ['users.id', 'users.name', 'users.username', 'users.avatar_url', 'users.badge']);

        $followingIds = $tab === 'followers'
            ? Follow::where('follower_id', $user->id)
                ->whereIn('following_id', $page->pluck('id'))
                ->pluck('following_id')
                ->all()
            : [];

        return response()->json([
            'data' => $page->getCollection()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'avatar_url' => $u->avatar_url,
                'badge' => $u->badge,
                // Lets the UI mark people who follow you back
                'is_following' => $tab === 'following' || in_array($u->id, $followingIds, true),
            ])->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
            'counts' => [
                'following' => Follow::where('follower_id', $user->id)->count(),
                'followers' => Follow::where('following_id', $user->id)->count(),
            ],
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

    private const ACTIVITY_KEEP = 10;

    private function buildActivity(User $user): array
    {
        // The feed only ever shows the newest N entries, so everything older is
        // dead weight — prune it here instead of letting the table grow forever.
        $keep = Activity::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id') // tiebreaker for equal timestamps (e.g. seeded rows)
            ->limit(self::ACTIVITY_KEEP)
            ->pluck('id');

        if ($keep->count() === self::ACTIVITY_KEEP) {
            Activity::where('user_id', $user->id)
                ->whereNotIn('id', $keep)
                ->delete();
        }

        return Activity::whereIn('id', $keep)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (Activity $activity) {
                $d = $activity->data;

                return match ($activity->type) {
                    'catch' => [
                        'type' => 'catch',
                        'data' => [
                            'fish_name' => $d['fish_name'] ?? '',
                            'weight' => $d['weight'] ?? '',
                            'lake_name' => $d['lake_name'] ?? '',
                        ],
                        'created_at' => $activity->created_at->toISOString(),
                    ],
                    'following' => [
                        'type' => 'following',
                        'data' => [
                            'name' => $d['following_name'] ?? '',
                            'id' => $d['following_id'] ?? null,
                        ],
                        'created_at' => $activity->created_at->toISOString(),
                    ],
                    'follower' => [
                        'type' => 'follower',
                        'data' => [
                            'name' => $d['follower_name'] ?? '',
                            'id' => $d['follower_id'] ?? null,
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
