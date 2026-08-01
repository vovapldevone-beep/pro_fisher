<?php

namespace Database\Seeders;

use App\Models\Lake;
use App\Models\LakePhoto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Water bodies around Warsaw, parsed from Google Maps into
 * `database/seeders/data/warsaw_lakes.json`.
 *
 * Idempotent, and safe to run on a database that already has hand-curated
 * lakes: rows listed in ALIASES fold into the existing record rather than
 * standing up a second card for the same pond.
 */
class WarsawLakeSeeder extends Seeder
{
    /**
     * Parser name => name of the lake already in the database.
     *
     * These six were entered by hand before the import. Their coordinates are
     * approximate (Koszajec is 3.2 km out) so proximity alone will not find
     * them — the pairing is by address and phone number, and is fixed here.
     */
    private const ALIASES = [
        'Halinów' => 'Łowisko specjalne Halinów',
        'Stara Cegielnia' => 'Łowisko Stara Cegielnia',
        'Perła Mazowsza Bielawa' => 'Fishery Perla Mazovia Bielawa',
        'Lindis' => 'Łowisko Lindis',
        'Rusiec' => 'Łowisko Rusiec',
        'Koszajec' => 'Łowisko Koszajec',
    ];

    /**
     * Taken from the parser even when the existing lake has a value: the old
     * rows carry the 4.5 default and a hand-placed pin, both worse than Google.
     */
    private const FROM_GOOGLE = ['rating', 'reviews_count', 'latitude', 'longitude'];

    /** Everything an operator may have written by hand — filled only when empty. */
    private const FILL_IF_EMPTY = [
        'description', 'address', 'region', 'fish_species', 'area_ha',
        'max_depth_m', 'admin_name', 'admin_website', 'rules', 'price',
    ];

    public function run(): void
    {
        $path = database_path('seeders/data/warsaw_lakes.json');

        if (! is_file($path)) {
            $this->command?->error("Missing data file: {$path}");

            return;
        }

        $rows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $created = $merged = $updated = $photos = 0;

        foreach ($rows as $row) {
            $photoUrls = $row['photos'] ?? [];
            unset($row['photos']);

            // Every score in this file is Google's, so the UI can label it.
            $row['rating_source'] = 'google';

            if ($existing = $this->aliasTarget($row['name'])) {
                $existing->update($this->mergeableFields($row, $existing));
                $merged++;
                $photos += $this->attachPhotos($existing, $photoUrls);

                continue;
            }

            // Slug is derived rather than stored in the file, so it stays unique
            // against whatever else is in the table.
            $lake = Lake::where('name', $row['name'])->first();
            $row['slug'] = $lake?->slug ?? $this->uniqueSlug($row['name']);

            $lake = Lake::updateOrCreate(['slug' => $row['slug']], $row);
            $lake->wasRecentlyCreated ? $created++ : $updated++;

            $photos += $this->attachPhotos($lake, $photoUrls);
        }

        $this->command?->info(
            "Warsaw lakes: {$created} created, {$updated} updated, {$merged} merged into existing, {$photos} photos added."
        );
    }

    private function aliasTarget(string $name): ?Lake
    {
        return isset(self::ALIASES[$name])
            ? Lake::where('name', self::ALIASES[$name])->first()
            : null;
    }

    /** The subset of a parsed row that may be written over a curated lake. */
    private function mergeableFields(array $row, Lake $existing): array
    {
        $changes = ['rating_source' => 'google'];

        foreach (self::FROM_GOOGLE as $field) {
            $changes[$field] = $row[$field];
        }

        foreach (self::FILL_IF_EMPTY as $field) {
            if (($existing->$field === null || $existing->$field === '') && ($row[$field] ?? null) !== null) {
                $changes[$field] = $row[$field];
            }
        }

        // Same number in a better shape: "603932204" -> "+48 603 932 204".
        // Guarded on the digits matching, so it can never swap in another line.
        $digits = fn (?string $s) => ltrim(preg_replace('/\D/', '', (string) $s), '48');

        if (($row['admin_phone'] ?? null) && $digits($existing->admin_phone) === $digits($row['admin_phone'])) {
            $changes['admin_phone'] = $row['admin_phone'];
        } elseif ($existing->admin_phone === null) {
            $changes['admin_phone'] = $row['admin_phone'] ?? null;
        }

        return $changes;
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
