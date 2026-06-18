<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatchRecord;
use App\Models\PostLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggle(CatchRecord $catchRecord, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $existing = PostLike::where('user_id', $userId)
            ->where('catch_id', $catchRecord->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            PostLike::create(['user_id' => $userId, 'catch_id' => $catchRecord->id]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => PostLike::where('catch_id', $catchRecord->id)->count(),
        ]);
    }
}
