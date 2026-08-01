<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CatchRecord;
use Illuminate\Support\Collection;

/**
 * The display-only achievement list, computed from a user's catches.
 *
 * Shared by the cabinet and the public fisher profile so the same account
 * cannot show one set of badges to its owner and another to a visitor — the
 * fisher page used to keep its own copy with lowered thresholds ("Фотограф
 * 50 фото" was earned at a single photo), which read as plain nonsense next
 * to a profile holding four publications.
 *
 * Stateful achievements (the fish hunt) live in `user_achievements`, not here.
 */
trait BuildsAchievements
{
    protected function countNightCatches(Collection $catches): int
    {
        return $catches->filter(
            fn ($c) => $c->caught_at && ($c->caught_at->hour >= 22 || $c->caught_at->hour < 6)
        )->count();
    }

    protected function buildAchievements(
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
}
