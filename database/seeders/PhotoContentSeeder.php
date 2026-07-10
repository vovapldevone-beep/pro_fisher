<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\CatchRecord;
use App\Models\Lake;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PhotoContentSeeder extends Seeder
{
    /**
     * Creates exactly one publication per photo already sitting in
     * storage/app/public/catches, named `user{id}_{n}.{ext}`.
     *
     * The count comes from the disk rather than a random number, so the records
     * and the files can never drift apart: copy the folder to another server,
     * run the seeder, and every card has its image.
     *
     * Everything is derived from `{id}` and `{n}` — no randomness — which makes
     * the result identical on every machine.
     */
    private const EXCLUDED_USER_IDS = [9, 10];

    private array $fishNames = [
        'Щука', 'Короп', 'Лящ', 'Окунь', 'Судак', 'Сом',
        'Карась', 'Плітка', 'Товстолоб', 'Амур', 'Форель', 'Лин',
    ];

    private array $catchNotes = [
        'Клювало з самого ранку, вода спокійна.',
        'Взяв на спінінг, боровся хвилин п\'ять.',
        'Найкращий улов цього сезону!',
        'Ловив на фідер, приманка — кукурудза.',
        'Вечірня риболовля вдалася на славу.',
        'Довго чекав, але воно того вартувало.',
    ];

    private array $postNotes = [
        'Ранкова атмосфера на озері 🌅',
        'Готуємо снасті до нового сезону.',
        'Нове місце, нові враження!',
        'Туман над водою — краса неймовірна.',
        'Вихідні на природі з друзями 🎣',
        'Захід сонця над водою. Заради цього і їдемо.',
    ];

    private array $locations = [
        'Озеро Біле', 'Дністер, Заліщики', 'Київське водосховище',
        'Дніпро, Черкаси', 'Ставок у Дрогобичі', 'Десна, Чернігів',
    ];

    public function run(): void
    {
        $photosByUser = $this->scanPhotos();

        if (! $photosByUser) {
            $this->command->warn('У storage/app/public/catches немає файлів user{id}_{n}.{ext}.');

            return;
        }

        $lakeIds = Lake::pluck('id')->all();
        if (! $lakeIds) {
            $this->command->warn('Немає озер — спочатку запусти LakeSeeder.');

            return;
        }

        $created = 0;

        foreach ($photosByUser as $userId => $photos) {
            if (in_array($userId, self::EXCLUDED_USER_IDS, true)) {
                $this->command->comment("u{$userId}: пропущено (у списку виключень).");

                continue;
            }

            $user = User::find($userId);
            if (! $user) {
                $this->command->comment("u{$userId}: пропущено (немає такого користувача).");

                continue;
            }

            // Idempotent: a rerun must not duplicate a feed
            if (CatchRecord::where('user_id', $userId)->exists()) {
                $this->command->comment("u{$userId}: пропущено (вже має публікації).");

                continue;
            }

            ksort($photos);

            foreach ($photos as $n => $path) {
                $this->createRecord($user, $lakeIds, $path, $n);
                $created++;
            }

            $this->command->info("u{$userId} ({$user->name}): ".count($photos).' публікацій.');
        }

        $this->command->info("Готово: {$created} публікацій, по одній на фото.");
    }

    /**
     * @return array<int, array<int, string>> [user_id => [n => 'catches/userX_n.jpg']]
     */
    private function scanPhotos(): array
    {
        $result = [];

        foreach (Storage::disk('public')->files('catches') as $path) {
            if (preg_match('/^catches\/user(\d+)_(\d+)\.[a-zA-Z]+$/', $path, $m)) {
                $result[(int) $m[1]][(int) $m[2]] = $path;
            }
        }

        ksort($result);

        return $result;
    }

    private function createRecord(User $user, array $lakeIds, string $photo, int $n): void
    {
        // Odd photos become catches, even ones posts — deterministic, so the same
        // file always yields the same kind of record.
        $isCatch = $n % 2 === 1;

        // Two weeks apart, oldest first, so feeds are ordered and reproducible
        $createdAt = now()->subDays($n * 14)->startOfHour();

        if (! $isCatch) {
            $post = new CatchRecord([
                'user_id' => $user->id,
                'type' => 'post',
                'photo' => $photo,
                'notes' => $this->pick($this->postNotes, $user->id + $n),
                'location' => $this->pick($this->locations, $user->id + $n),
            ]);
            $this->saveWithTimestamps($post, $createdAt);

            return;
        }

        $lakeId = $lakeIds[($user->id + $n) % count($lakeIds)];
        $fishName = $this->pick($this->fishNames, $user->id + $n);
        $weight = round(1 + (($user->id * 7 + $n * 3) % 150) / 10, 2);

        $catch = new CatchRecord([
            'user_id' => $user->id,
            'lake_id' => $lakeId,
            'type' => 'catch',
            'fish_name' => $fishName,
            'weight' => $weight,
            'photo' => $photo,
            'caught_at' => $createdAt->toDateString(),
            'notes' => $this->pick($this->catchNotes, $user->id + $n),
        ]);
        $this->saveWithTimestamps($catch, $createdAt);

        // Mirrors CatchController, so the cabinet activity feed is populated
        $activity = new Activity([
            'user_id' => $user->id,
            'catch_id' => $catch->id,
            'type' => 'catch',
            'data' => [
                'fish_name' => $fishName,
                'weight' => $weight,
                'lake_name' => $catch->lake?->name,
            ],
        ]);
        $this->saveWithTimestamps($activity, $createdAt);
    }

    private function pick(array $list, int $seed): string
    {
        return $list[$seed % count($list)];
    }

    /** `created_at` is not in #[Fillable], so mass assignment would silently drop it. */
    private function saveWithTimestamps($model, $createdAt): void
    {
        $model->created_at = $createdAt;
        $model->updated_at = $createdAt;
        $model->save();
    }
}
