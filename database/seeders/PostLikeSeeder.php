<?php

namespace Database\Seeders;

use App\Models\CatchRecord;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostLikeSeeder extends Seeder
{
    private const MIN_LIKES = 2;

    private const MAX_LIKES = 15;

    public function run(): void
    {
        // Only demo accounts hand out likes — we must not fabricate actions by real people.
        // Real users' publications still receive them, which is the point of the filler.
        $userIds = User::where('email', 'like', '%@'.DemoUserSeeder::EMAIL_DOMAIN)->pluck('id')->all();
        $catches = CatchRecord::select('id', 'user_id', 'created_at')->get();

        if (count($userIds) < self::MIN_LIKES || $catches->isEmpty()) {
            $this->command->warn('Замало демо-користувачів або публікацій — сідер пропущено.');

            return;
        }

        // `post_likes` is unique per (user_id, catch_id), so a publication can never
        // gather more likes than there are users willing to give them.
        $ceiling = min(self::MAX_LIKES, count($userIds));

        if ($ceiling < self::MAX_LIKES) {
            $this->command->warn("Користувачів лише ".count($userIds).' — максимум лайків обмежено до '.$ceiling.'.');
        }

        // Existing likes are real data — top up to the target, never wipe and recreate
        $existing = PostLike::select('catch_id', 'user_id')
            ->get()
            ->groupBy('catch_id')
            ->map(fn ($group) => $group->pluck('user_id')->all());

        $rows = [];

        foreach ($catches as $catch) {
            $alreadyLiked = $existing[$catch->id] ?? [];

            // Idempotent: a publication that already reached the minimum is left alone,
            // otherwise every rerun would pile on a fresh random target.
            if (count($alreadyLiked) >= self::MIN_LIKES) {
                continue;
            }

            // The author does not like their own publication
            $candidates = array_values(array_diff($userIds, [$catch->user_id], $alreadyLiked));
            if (! $candidates) {
                continue;
            }

            $target = random_int(self::MIN_LIKES, $ceiling);
            $missing = min($target - count($alreadyLiked), count($candidates));
            if ($missing <= 0) {
                continue;
            }

            shuffle($candidates);

            foreach (array_slice($candidates, 0, $missing) as $userId) {
                // Likes land between the publication date and now, never before it
                $minutesSince = (int) $catch->created_at->diffInMinutes(now());
                $likedAt = $catch->created_at->copy()->addMinutes(random_int(5, max(6, $minutesSince)));

                $rows[] = [
                    'user_id' => $userId,
                    'catch_id' => $catch->id,
                    'created_at' => $likedAt,
                    'updated_at' => $likedAt,
                ];
            }
        }

        // Chunked: a single INSERT with thousands of rows can exceed max_allowed_packet
        foreach (array_chunk($rows, 500) as $chunk) {
            PostLike::insert($chunk);
        }

        $total = PostLike::count();
        $perCatch = round($total / $catches->count(), 1);
        $this->command->info('Додано лайків: '.count($rows).". Всього: {$total} на {$catches->count()} публікацій (в середньому {$perCatch}).");
    }
}
