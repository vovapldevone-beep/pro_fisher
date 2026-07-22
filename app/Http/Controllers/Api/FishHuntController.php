<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAchievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The "find 10 hidden fish" easter egg. Progress is a single counter stored in
 * user_achievements under key 'fish_hunt' — placement of the fish is entirely
 * client-side and reshuffled per visit, so the server only needs the tally.
 */
class FishHuntController extends Controller
{
    private const KEY = 'fish_hunt';

    private const TOTAL = 10;

    public function progress(Request $request): JsonResponse
    {
        $found = UserAchievement::where('user_id', $request->user()->id)
            ->where('key', self::KEY)
            ->value('progress') ?? 0;

        return response()->json(['found' => $found, 'total' => self::TOTAL]);
    }

    public function find(Request $request): JsonResponse
    {
        $achievement = UserAchievement::firstOrCreate(
            ['user_id' => $request->user()->id, 'key' => self::KEY],
            ['progress' => 0],
        );

        // Cap at TOTAL; increment() is atomic so concurrent clicks can't overshoot
        if ($achievement->progress < self::TOTAL) {
            $achievement->increment('progress');

            if ($achievement->progress >= self::TOTAL && ! $achievement->completed_at) {
                $achievement->update(['completed_at' => now()]);
            }
        }

        return response()->json([
            'found' => $achievement->progress,
            'total' => self::TOTAL,
        ]);
    }
}
