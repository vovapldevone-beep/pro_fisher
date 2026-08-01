<?php

namespace Database\Seeders;

use App\Models\Lake;
use App\Models\LakePhoto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Water bodies of the Lviv region, parsed from Google Maps into
 * `database/seeders/data/lviv_lakes.json` (entries 0–26 of the parser dump).
 *
 * Idempotent: keyed by slug, so a second run updates the same rows instead of
 * standing up a second card. Unlike WarsawLakeSeeder there is no alias table —
 * the database held no Ukrainian lakes at all when this was written, so there
 * is nothing to merge into.
 */
class LvivLakeSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/lviv_lakes.json');

        if (! is_file($path)) {
            $this->command?->error("Missing data file: {$path}");

            return;
        }

        $rows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $created = $updated = $photos = 0;

        foreach ($rows as $row) {
            $photoUrls = $row['photos'] ?? [];
            unset($row['photos']);

            // Every score in this file is Google's, so the UI labels it as such
            // and SpaController leaves it out of the schema.org markup.
            $row['rating_source'] = 'google';

            // Derived rather than stored in the file, so it stays unique against
            // whatever else is already in the table. Str::slug transliterates
            // the Cyrillic: "Золота Форель" -> "zolota-forel".
            $lake = Lake::where('name', $row['name'])->first();
            $row['slug'] = $lake?->slug ?? $this->uniqueSlug($row['name']);

            $lake = Lake::updateOrCreate(['slug' => $row['slug']], $row);
            $lake->wasRecentlyCreated ? $created++ : $updated++;

            $photos += $this->attachPhotos($lake, $photoUrls);
        }

        $this->command?->info(
            "Lviv lakes: {$created} created, {$updated} updated, {$photos} photos added."
        );
    }

    /**
     * Photos are written once. An admin who curated the gallery by hand should
     * not get the parser's set appended back on the next run.
     */
    private function attachPhotos(Lake $lake, array $urls): int
    {
        if (! $urls || $lake->photos()->exists()) {
            return 0;
        }

        foreach (array_values($urls) as $index => $url) {
            LakePhoto::create([
                'lake_id' => $lake->id,
                'path' => $url,
                'is_primary' => $index === 0,
                'sort_order' => $index,
            ]);
        }

        return count($urls);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'lake';
        $slug = $base;
        $i = 1;

        while (Lake::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
