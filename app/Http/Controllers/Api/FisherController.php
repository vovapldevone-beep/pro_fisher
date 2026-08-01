<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\BuildsAchievements;
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
    use BuildsAchievements;

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
        $nightCatchesCount = $this->countNightCatches($catches);
        $totalLikes = PostLike::whereIn('catch_id', $catches->pluck('id'))->count();

        $isFollowing = $request->user()
            ? Follow::where('follower_id', $request->user()->id)->where('following_id', $user->id)->exists()
            : false;

        return response()->json([
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
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
            ],
            'achievements' => $this->buildAchievements($catchesCount, $lakesVisited, $photosCount, $biggestCatch, $followersCount, $nightCatchesCount),
            'is_following' => $isFollowing,
        ]);
    }

    /**
     * Finds people by display name or @handle, a page at a time.
     *
     * Paginated because the result set is unbounded — a two-letter query matches
     * a large share of the table, and the friends modal scrolls the rest in.
     */
    public function search(Request $request): JsonResponse
    {
        // A pasted handle usually carries its "@" along
        $term = ltrim(trim((string) $request->query('q', '')), '@');

        if (mb_strlen($term) < 2) {
            return response()->json([
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0],
            ]);
        }

        $me = $request->user();
        // The wildcards are ours; the ones the user typed must stay literal
        $like = '%'.addcslashes($term, '%_\\').'%';

        $page = User::query()
            ->where('is_blocked', false)
            ->where('id', '!=', $me->id)
            ->where(fn ($query) => $query
                ->where('name', 'like', $like)
                ->orWhere('username', 'like', $like))
            // Exact handle first, then things that start with the term
            ->orderByRaw(
                'CASE WHEN username = ? THEN 0 WHEN username LIKE ? THEN 1 WHEN name LIKE ? THEN 2 ELSE 3 END',
                [$term, $term.'%', $term.'%'],
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15, ['id', 'name', 'username', 'avatar_url', 'badge']);

        $followingIds = Follow::where('follower_id', $me->id)
            ->whereIn('following_id', $page->pluck('id'))
            ->pluck('following_id')
            ->all();

        return response()->json([
            'data' => $page->getCollection()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'avatar_url' => $u->avatar_url,
                'badge' => $u->badge,
                'is_following' => in_array($u->id, $followingIds, true),
            ])->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
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
                'postLikes' => fn ($q) => $q->where('user_id', $userId)->select('id', 'catch_id'),
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
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
}
