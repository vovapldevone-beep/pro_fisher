<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoUserSeeder extends Seeder
{
    /** Marks an account as demo data — real users must never match this. */
    public const EMAIL_DOMAIN = 'profisher.test';

    /**
     * Filler accounts so that likes and follower lists look plausible.
     * `post_likes` is unique per (user_id, catch_id), so the number of users
     * is a hard ceiling on how many likes a publication can collect.
     */
    private array $names = [
        'Андрій Мороз', 'Олег Ткаченко', 'Марія Гуменюк', 'Тарас Бондар',
        'Ірина Левченко', 'Богдан Кравець', 'Наталя Скрипник', 'Юрій Панасюк',
        'Оксана Дзюба', 'Віктор Романюк', 'Софія Кравчук', 'Ігор Шевчук',
        'Леся Гончар', 'Роман Стеценко', 'Дмитро Пилипчук', 'Катерина Мельник',
        'Павло Осадчий', 'Вікторія Лисенко', 'Микола Гаврилюк', 'Аліна Ткачук',
    ];

    public function run(): void
    {
        $created = 0;

        foreach ($this->names as $name) {
            $email = Str::slug($name, '.').'@'.self::EMAIL_DOMAIN;

            // Idempotent: re-running the seeder must not duplicate accounts
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'username' => User::generateUsername($name),
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );

            if ($user->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("Демо-користувачів створено: {$created} (всього в базі: ".User::count().').');
    }
}
