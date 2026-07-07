<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CatchResource;
use App\Models\Activity;
use App\Models\CatchRecord;
use App\Models\Follow;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FisherController extends Controller
{
    public function show(User $user, Request $request): JsonResponse
    {
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
        $totalLikes = PostLike::whereIn('catch_id', $catches->pluck('id'))->count();

        $isFollowing = $request->user()
            ? Follow::where('follower_id', $request->user()->id)->where('following_id', $user->id)->exists()
            : false;

        return response()->json([
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
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
                'total_likes' => $totalLikes,
                'ranking' => 0,
            ],
            'achievements' => $this->buildAchievements($catchesCount, $lakesVisited, $photosCount, $biggestCatch),
            'is_following' => $isFollowing,
        ]);
    }

    public function follow(User $user, Request $request): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Не можна підписатись на себе.'], 422);
        }

        $alreadyFollowing = Follow::where('follower_id', $request->user()->id)
            ->where('following_id', $user->id)
            ->exists();

        Follow::firstOrCreate([
            'follower_id' => $request->user()->id,
            'following_id' => $user->id,
        ]);

        if (! $alreadyFollowing) {
            Activity::create([
                'user_id' => $request->user()->id,
                'type' => 'following',
                'data' => ['following_id' => $user->id, 'following_name' => $user->name],
            ]);
            Activity::create([
                'user_id' => $user->id,
                'type' => 'follower',
                'data' => ['follower_id' => $request->user()->id, 'follower_name' => $request->user()->name],
            ]);
        }

        return response()->json(['following' => true]);
    }

    public function unfollow(User $user, Request $request): JsonResponse
    {
        Follow::where('follower_id', $request->user()->id)
            ->where('following_id', $user->id)
            ->delete();

        return response()->json(['following' => false]);
    }

    public function posts(User $user, Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        $query = CatchRecord::query()
            ->where('user_id', $user->id)
            ->with(['lake:id,name,slug,latitude,longitude'])
            ->withCount(['postLikes', 'catchComments'])
            ->when($userId, fn ($q) => $q->with([
                'postLikes'    => fn ($q) => $q->where('user_id', $userId)->select('id', 'catch_id'),
                'userComments' => fn ($q) => $q->where('user_id', $userId)->select('id', 'catch_id'),
            ]))
            ->latest('created_at');

        if (in_array($request->type, ['post', 'catch'])) {
            $query->where('type', $request->type);
        }

        $paginated = $query->paginate(12);

        return response()->json([
            'data' => CatchResource::collection($paginated),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    private function buildAchievements(int $catchesCount, int $lakesVisited, int $photosCount, ?CatchRecord $biggestCatch): array
    {
        $hasBigCarp = $biggestCatch && $biggestCatch->weight >= 10
            && stripos($biggestCatch->fish_name, 'karp') !== false;

        return [
            ['id' => 'catches_100', 'title' => '100 уловів', 'icon' => 'star', 'earned' => $catchesCount >= 100],
            ['id' => 'big_carp', 'title' => 'Перший короп 10+ кг', 'icon' => 'fish', 'earned' => $hasBigCarp || $catchesCount >= 3],
            ['id' => 'explorer', 'title' => 'Відвідав 25 озер', 'icon' => 'lake', 'earned' => $lakesVisited >= 25 || $lakesVisited >= 3],
            ['id' => 'photographer', 'title' => 'Фотограф 50 фото уловів', 'icon' => 'camera', 'earned' => $photosCount >= 50 || $photosCount >= 1],
            ['id' => 'night', 'title' => 'Нічний рибалка 10 нічних уловів', 'icon' => 'moon', 'earned' => $catchesCount >= 5],
        ];
    }
}
