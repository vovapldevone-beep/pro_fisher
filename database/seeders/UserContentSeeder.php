<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\CatchRecord;
use App\Models\Lake;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserContentSeeder extends Seeder
{
    /**
     * Photos are referenced, not uploaded: each record points at
     * storage/app/public/catches/user{id}_{n}.jpg. Drop the files in later and
     * they appear — the numbering restarts at 1 for every user.
     */
    private const PHOTO_EXTENSION = 'jpg';

    private const MIN_RECORDS = 2;

    private const MAX_RECORDS = 10;

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
        'Погода була так собі, а риба брала чудово.',
        'Відпустив назад — нехай росте.',
    ];

    private array $postNotes = [
        'Ранкова атмосфера на озері 🌅',
        'Готуємо снасті до нового сезону.',
        'Нове місце, нові враження!',
        'Туман над водою — краса неймовірна.',
        'Підготовка прикормки з вечора.',
        'Вихідні на природі з друзями 🎣',
        'Тестую новий спінінг, враження чудові.',
        'Захід сонця над водою. Заради цього і їдемо.',
    ];

    private array $locations = [
        'Озеро Біле', 'Дністер, Заліщики', 'Київське водосховище',
        'Дніпро, Черкаси', 'Ставок у Дрогобичі', 'Десна, Чернігів',
    ];

    public function run(): void
    {
        // Never touch real accounts: demo content belongs only to demo users.
        // This is what makes the seeder safe to run against production.
        $users = User::where('email', 'like', '%@'.DemoUserSeeder::EMAIL_DOMAIN)->get();
        $lakeIds = Lake::pluck('id')->all();

        if ($users->isEmpty()) {
            $this->command->warn('Немає демо-користувачів — спочатку запусти DemoUserSeeder.');

            return;
        }

        if (empty($lakeIds)) {
            $this->command->warn('Немає озер — спочатку запусти LakeSeeder.');

            return;
        }

        $totalCatches = 0;
        $totalPosts = 0;
        $skipped = 0;

        foreach ($users as $user) {
            // Idempotent: a second run must not double a user's feed
            if (CatchRecord::where('user_id', $user->id)->exists()) {
                $skipped++;

                continue;
            }

            $count = random_int(self::MIN_RECORDS, self::MAX_RECORDS);

            for ($i = 1; $i <= $count; $i++) {
                $isCatch = random_int(0, 1) === 1;
                $photo = sprintf('catches/user%d_%d.%s', $user->id, $i, self::PHOTO_EXTENSION);

                // Spread records over the last three months so feeds look alive
                $createdAt = now()->subDays(random_int(0, 90))->subHours(random_int(0, 23));

                if ($isCatch) {
                    $this->createCatch($user, $lakeIds, $photo, $createdAt);
                    $totalCatches++;
                } else {
                    $this->createPost($user, $photo, $createdAt);
                    $totalPosts++;
                }
            }

            $this->command->info("{$user->name} (id {$user->id}): {$count} записів → user{$user->id}_1 … user{$user->id}_{$count}");
        }

        if ($skipped) {
            $this->command->comment("Пропущено {$skipped} користувачів — у них уже є публікації.");
        }

        $this->command->info("Готово: {$totalCatches} уловів, {$totalPosts} постів.");
    }

    private function createCatch(User $user, array $lakeIds, string $photo, $createdAt): void
    {
        $lakeId = $lakeIds[array_rand($lakeIds)];
        $fishName = $this->fishNames[array_rand($this->fishNames)];
        $weight = round(random_int(50, 1800) / 100, 2);

        $catch = new CatchRecord([
            'user_id' => $user->id,
            'lake_id' => $lakeId,
            'type' => 'catch',
            'fish_name' => $fishName,
            'weight' => $weight,
            'photo' => $photo,
            'caught_at' => $createdAt->toDateString(),
            'notes' => $this->catchNotes[array_rand($this->catchNotes)],
        ]);
        $this->saveWithTimestamps($catch, $createdAt);

        // Mirrors what CatchController does, so the cabinet activity feed is populated
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

    private function createPost(User $user, string $photo, $createdAt): void
    {
        $post = new CatchRecord([
            'user_id' => $user->id,
            'type' => 'post',
            'photo' => $photo,
            'notes' => $this->postNotes[array_rand($this->postNotes)],
            'location' => random_int(0, 1) === 1 ? $this->locations[array_rand($this->locations)] : null,
        ]);
        $this->saveWithTimestamps($post, $createdAt);
    }

    /**
     * `created_at` is not in the models' #[Fillable], so mass assignment silently drops it.
     * Setting the attributes before save() marks them dirty, and Eloquent leaves them alone.
     */
    private function saveWithTimestamps($model, $createdAt): void
    {
        $model->created_at = $createdAt;
        $model->updated_at = $createdAt;
        $model->save();
    }
}
