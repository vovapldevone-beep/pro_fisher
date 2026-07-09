<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CatchResource;
use App\Http\Resources\LakeResource;
use App\Models\CatchRecord;
use App\Models\Lake;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HomeController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'lakes_count' => Lake::count(),
            'catches_count' => CatchRecord::count(),
            'users_count' => User::count(),
            'contests_count' => 53,
        ]);
    }

    public function popularLakes(): AnonymousResourceCollection
    {
        $lakes = Lake::query()
            ->with(['photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->limit(1)])
            ->withCount('catchRecords')
            ->orderByDesc('catch_records_count')
            ->orderByDesc('rating')
            ->limit(4)
            ->get();

        return LakeResource::collection($lakes);
    }

    public function recentCatches(): AnonymousResourceCollection
    {
        $catches = CatchRecord::query()
            ->with(['user:id,name', 'lake:id,name,slug'])
            ->latest('caught_at')
            ->limit(4)
            ->get();

        return CatchResource::collection($catches);
    }

    public function posts(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()?->id;

        $catches = CatchRecord::query()
            ->with(['user:id,name,avatar_url', 'lake:id,name,slug,latitude,longitude'])
            ->withCount(['postLikes', 'catchComments'])
            ->when($userId, fn ($q) => $q->with([
                'postLikes'     => fn ($q) => $q->where('user_id', $userId)->select('id', 'catch_id'),
                'userComments'  => fn ($q) => $q->where('user_id', $userId)->select('id', 'catch_id'),
            ]))
            ->latest('created_at')
            ->get();

        return CatchResource::collection($catches);
    }
}
