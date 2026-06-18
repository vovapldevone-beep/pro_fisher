<?php

namespace Database\Seeders;

use App\Models\CatchRecord;
use App\Models\Lake;
use App\Models\LakePhoto;
use App\Models\LakeReview;
use App\Models\Permit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LakeSeeder extends Seeder
{
    public function run(): void
    {
        $lakes = [
            [
                'name' => 'Śniardwy',
                'description' => 'Największe jezioro w Polsce, położone na Mazurach. Doskonałe miejsce na połów szczupaka i okonia.',
                'latitude' => 53.7833,
                'longitude' => 21.7167,
                'price' => 45.00,
                'rating' => 4.8,
                'region' => 'Warmińsko-Mazurskie',
                'address' => 'Mazury, powiat piski',
            ],
            [
                'name' => 'Mamry',
                'description' => 'Złożony system jezior mazurskich połączonych kanałami. Popularne wśród wędkarzy spinningowych.',
                'latitude' => 54.0000,
                'longitude' => 21.7500,
                'price' => 40.00,
                'rating' => 4.6,
                'region' => 'Warmińsko-Mazurskie',
                'address' => 'Giżycko, Mazury',
            ],
            [
                'name' => 'Niegocin',
                'description' => 'Jezioro w regionie Mazur, znane z czystej wody i obfitości ryb drapieżnych.',
                'latitude' => 54.0333,
                'longitude' => 21.7667,
                'price' => 35.00,
                'rating' => 4.5,
                'region' => 'Warmińsko-Mazurskie',
                'address' => 'Giżycko',
            ],
            [
                'name' => 'Żarnowieckie',
                'description' => 'Jezioro na Półwyspie Helskim, słynące z połowów sandacza i leszcza.',
                'latitude' => 54.7667,
                'longitude' => 18.0833,
                'price' => 30.00,
                'rating' => 4.3,
                'region' => 'Pomorskie',
                'address' => 'Żarnowiec, powiat pucki',
            ],
            [
                'name' => 'Drawsko',
                'description' => 'Drugie co do wielkości jezioro w Polsce, położone w Drawskim Parku Krajobrazowym.',
                'latitude' => 53.3167,
                'longitude' => 15.7833,
                'price' => 25.00,
                'rating' => 4.4,
                'region' => 'Zachodniopomorskie',
                'address' => 'Drawsko Pomorskie',
            ],
            [
                'name' => 'Gopło',
                'description' => 'Jezioro na Kujawach, historyczne miejsce związane z legendą o Popielu.',
                'latitude' => 52.6167,
                'longitude' => 18.6833,
                'price' => 20.00,
                'rating' => 4.1,
                'region' => 'Kujawsko-Pomorskie',
                'address' => 'Kruszwica',
            ],
            [
                'name' => 'Białe',
                'description' => 'Jezioro w Bieszczadach, otoczone gęstymi lasami. Idealne na spokojną wędkę.',
                'latitude' => 49.2833,
                'longitude' => 22.4167,
                'price' => 15.00,
                'rating' => 4.7,
                'region' => 'Podkarpackie',
                'address' => 'Bieszczady',
            ],
            [
                'name' => 'Solińskie',
                'description' => 'Sztuczne jezioro zaporowe na Sanie, jedno z największych zbiorników wodnych w Polsce.',
                'latitude' => 49.3667,
                'longitude' => 22.4500,
                'price' => 28.00,
                'rating' => 4.6,
                'region' => 'Podkarpackie',
                'address' => 'Solina, Bieszczady',
            ],
            [
                'name' => 'Rożnowskie',
                'description' => 'Jezioro zaporowe na Dunajcu w Małopolsce, popularne wśród wędkarzy.',
                'latitude' => 49.5833,
                'longitude' => 20.6833,
                'price' => 22.00,
                'rating' => 4.2,
                'region' => 'Małopolskie',
                'address' => 'Rożnów',
            ],
            [
                'name' => 'Wigry',
                'description' => 'Jezioro w Wigierskim Parku Narodowym, znane z krystalicznie czystej wody.',
                'latitude' => 54.0500,
                'longitude' => 23.0833,
                'price' => 32.00,
                'rating' => 4.5,
                'region' => 'Podlaskie',
                'address' => 'Wigry, Suwałki',
            ],
            [
                'name' => 'Hańcza',
                'description' => 'Najgłębsze jezioro w Polsce (108,5 m), położone w Pojezierzu Wschodniosuwalskim.',
                'latitude' => 54.2667,
                'longitude' => 22.8333,
                'price' => 18.00,
                'rating' => 4.3,
                'region' => 'Podlaskie',
                'address' => 'Pojezierze Suwalskie',
            ],
            [
                'name' => 'Łebsko',
                'description' => 'Jezioro w Słowińskim Parku Narodowym, połączone z Bałtykiem.',
                'latitude' => 54.7667,
                'longitude' => 17.3000,
                'price' => 26.00,
                'rating' => 4.4,
                'region' => 'Pomorskie',
                'address' => 'Słowiński Park Narodowy',
            ],
        ];

        $photoUrls = [
            'https://images.unsplash.com/photo-1439066615861-d1af74d74000?w=800',
            'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800',
            'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=800',
        ];

        $fishNames = ['Szczupak', 'Sandacz', 'Okoń', 'Leszcz', 'Karp', 'Płoć', 'Sum'];

        $users = [
            User::updateOrCreate(
                ['email' => 'rybak@example.com'],
                [
                    'name' => 'IvanFishing',
                    'password' => 'password',
                    'location' => 'Польща, Варшава',
                    'bio' => 'Люблю риболовлю та подорожі на природу!',
                    'avatar_url' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=200',
                    'badge' => 'Засновник',
                ]
            ),
            User::firstOrCreate(
                ['email' => 'piotr@example.com'],
                ['name' => 'PiotrWędka', 'password' => 'password']
            ),
        ];

        $reviewTemplates = [
            ['author_name' => 'AdamW', 'rating' => 5, 'comment' => 'Чудове місце для риболовлі! Зловив щуку 4.2 кг.'],
            ['author_name' => 'Marcin88', 'rating' => 4, 'comment' => 'Гарне озеро, але в вихідні багато людей.'],
            ['author_name' => 'PiotrPL', 'rating' => 5, 'comment' => 'Рекомендую! Чиста вода і багато риби.'],
        ];

        foreach ($lakes as $index => $lakeData) {
            $lake = Lake::create([
                ...$lakeData,
                'slug' => Str::slug($lakeData['name']),
                'fish_species' => 'Szczupak, Sandacz, Okoń, Leszcz, Karp, Karaś',
                'area_ha' => 200 + ($index * 45),
                'max_depth_m' => 15 + ($index * 2),
                'permit_required' => true,
                'admin_name' => 'Jan Kowalski',
                'admin_phone' => '+48 123 456 789',
                'admin_website' => 'www.'.Str::slug($lakeData['name']).'.pl',
                'reviews_count' => 126 - ($index * 8),
                'rules' => 'Риболовля дозволена з 6:00 до 22:00. Обов\'язковий дозвіл. Заборона на використання сіток. Мінімальний розмір щуки — 50 см.',
            ]);

            foreach ($photoUrls as $photoIndex => $url) {
                LakePhoto::create([
                    'lake_id' => $lake->id,
                    'path' => $url,
                    'is_primary' => $photoIndex === 0,
                    'sort_order' => $photoIndex,
                ]);
            }

            if ($index < 8) {
                CatchRecord::create([
                    'user_id' => $users[$index % count($users)]->id,
                    'lake_id' => $lake->id,
                    'fish_name' => $fishNames[$index % count($fishNames)],
                    'weight' => round(1.5 + ($index * 0.7), 2),
                    'caught_at' => now()->subHours($index * 3 + 2)->toDateString(),
                    'notes' => 'Świetny połów!',
                    'created_at' => now()->subHours($index * 3 + 2),
                    'updated_at' => now()->subHours($index * 3 + 2),
                ]);
            }

            foreach ($reviewTemplates as $reviewIndex => $review) {
                LakeReview::create([
                    ...$review,
                    'lake_id' => $lake->id,
                    'created_at' => now()->subDays($reviewIndex + 1),
                    'updated_at' => now()->subDays($reviewIndex + 1),
                ]);
            }
        }

        $niegocin = Lake::where('slug', 'niegocin')->first();
        if ($niegocin) {
            Permit::create([
                'user_id' => $users[0]->id,
                'lake_id' => $niegocin->id,
                'duration_days' => 30,
                'expires_at' => now()->addDays(18),
                'status' => 'active',
            ]);
        }
    }
}
