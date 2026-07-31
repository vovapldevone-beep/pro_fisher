<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatchComment;
use App\Models\CatchRecord;
use App\Models\Lake;
use App\Models\LakePhoto;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'users_count' => User::count(),
            'blocked_count' => User::where('is_blocked', true)->count(),
            'catches_count' => CatchRecord::where('type', 'catch')->count(),
            'posts_count' => CatchRecord::where('type', 'post')->count(),
            'likes_count' => PostLike::count(),
            'comments_count' => CatchComment::count(),
            'lakes_count' => Lake::count(),
        ]);
    }

    /**
     * Counts over time for the dashboard chart.
     *
     * `GET /api/admin/chart?metric=users|publications&days=30`
     *
     * Buckets are built in PHP and zero-filled: a day nobody registered on
     * produces no row, and a chart that silently dropped it would draw a
     * straight line between two distant dates. Grouping widens with the range
     * because at one or two records a day a long daily chart is just noise.
     */
    public function chart(Request $request): JsonResponse
    {
        $days = max(7, min(365, (int) $request->query('days', 90)));
        $metric = $request->query('metric') === 'publications' ? 'publications' : 'users';

        $from = now()->startOfDay()->subDays($days - 1);
        $buckets = $this->buckets($from, $days);

        $series = $metric === 'users'
            ? [$this->series(User::query(), $from, $buckets, 'users', 'Нові користувачі')]
            : [
                $this->series(
                    CatchRecord::where('type', 'post'), $from, $buckets, 'posts', 'Пости'
                ),
                // Legacy rows predate the column and are catches by default
                $this->series(
                    CatchRecord::where(fn ($q) => $q->where('type', 'catch')->orWhereNull('type')),
                    $from, $buckets, 'catches', 'Улови'
                ),
            ];

        return response()->json([
            'metric' => $metric,
            'days' => $days,
            'group' => $buckets['group'],
            'labels' => $buckets['labels'],
            'series' => $series,
        ]);
    }

    /**
     * The x axis: an ordered list of buckets plus the day => bucket-index map
     * used to fold daily counts into them.
     */
    private function buckets(Carbon $from, int $days): array
    {
        $group = match (true) {
            $days <= 31 => 'day',
            $days <= 120 => 'week',
            default => 'month',
        };

        $labels = [];
        $index = [];

        for ($day = $from->copy(); $day <= now()->startOfDay(); $day->addDay()) {
            $label = match ($group) {
                'day' => $day->format('d.m'),
                // Monday of the week / first of the month, so every day of the
                // same period lands on one label
                'week' => $day->copy()->startOfWeek()->format('d.m'),
                default => $day->format('m.Y'),
            };

            if (! in_array($label, $labels, true)) {
                $labels[] = $label;
            }

            $index[$day->toDateString()] = array_key_last($labels);
        }

        return ['group' => $group, 'labels' => $labels, 'index' => $index];
    }

    /**
     * One line of the chart. Grouping happens in PHP over daily counts rather
     * than in SQL, so it does not depend on MySQL-only date functions.
     */
    private function series($query, $from, array $buckets, string $key, string $label): array
    {
        $daily = $query
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $data = array_fill(0, count($buckets['labels']), 0);

        foreach ($daily as $day => $total) {
            // A driver may hand back a datetime string; the map is keyed by date
            $day = substr((string) $day, 0, 10);

            if (isset($buckets['index'][$day])) {
                $data[$buckets['index'][$day]] += (int) $total;
            }
        }

        return [
            'key' => $key,
            'label' => $label,
            'data' => $data,
            'total' => array_sum($data),
        ];
    }

    /**
     * Points for the lake heat map: `GET /api/admin/lakes/heat`.
     *
     * Intensity is the number of catches logged at the lake, so the map shows
     * where people actually fish rather than merely where lakes exist. Lakes
     * with no catches are still returned — the frontend gives them the floor
     * weight so an empty region is visibly empty, not missing.
     */
    public function lakesHeat(): JsonResponse
    {
        $lakes = Lake::query()
            ->withCount('catchRecords')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'name', 'slug', 'latitude', 'longitude']);

        return response()->json([
            'data' => $lakes->map(fn (Lake $lake) => [
                'id' => $lake->id,
                'name' => $lake->name,
                'slug' => $lake->slug,
                'lat' => (float) $lake->latitude,
                'lng' => (float) $lake->longitude,
                'catches' => $lake->catch_records_count,
            ]),
            'max_catches' => (int) $lakes->max('catch_records_count'),
        ]);
    }

    public function lakes(Request $request): JsonResponse
    {
        $lakes = Lake::with(['photos' => fn ($q) => $q->where('is_primary', true)->limit(1)])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $lakes->map(fn (Lake $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'slug' => $l->slug,
                'region' => $l->region,
                'latitude' => $l->latitude,
                'longitude' => $l->longitude,
                'price' => $l->price,
                'photo_url' => $l->photos->first()
                    ? (str_starts_with($l->photos->first()->path, 'http')
                        ? $l->photos->first()->path
                        : asset('storage/'.$l->photos->first()->path))
                    : null,
                'created_at' => $l->created_at->toDateString(),
            ]),
            'total' => $lakes->total(),
            'current_page' => $lakes->currentPage(),
            'last_page' => $lakes->lastPage(),
        ]);
    }

    public function storeLake(Request $request): JsonResponse
    {
        $validated = $this->validateLake($request);

        $validated['slug'] = $this->uniqueSlug($validated['name']);

        $lake = Lake::create($validated);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $file) {
                $path = $file->store('lakes', 'public');
                LakePhoto::create([
                    'lake_id' => $lake->id,
                    'path' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }

        return response()->json(['lake' => $this->lakeRow($lake)], 201);
    }

    public function showLake(Lake $lake): JsonResponse
    {
        return response()->json([
            'lake' => [
                'id' => $lake->id,
                'name' => $lake->name,
                'slug' => $lake->slug,
                'description' => $lake->description,
                'latitude' => $lake->latitude,
                'longitude' => $lake->longitude,
                'price' => $lake->price,
                'region' => $lake->region,
                'address' => $lake->address,
                'fish_species' => $lake->fish_species,
                'area_ha' => $lake->area_ha,
                'max_depth_m' => $lake->max_depth_m,
                'permit_required' => $lake->permit_required,
                'admin_name' => $lake->admin_name,
                'admin_phone' => $lake->admin_phone,
                'admin_website' => $lake->admin_website,
                'rules' => $lake->rules,
                'photos' => $lake->photos->map(fn (LakePhoto $p) => [
                    'id' => $p->id,
                    'url' => str_starts_with($p->path, 'http') ? $p->path : asset('storage/'.$p->path),
                    'is_primary' => $p->is_primary,
                ]),
            ],
        ]);
    }

    public function updateLake(Request $request, Lake $lake): JsonResponse
    {
        $validated = $this->validateLake($request);

        if ($validated['name'] !== $lake->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $lake->id);
        }

        $lake->update($validated);

        // Delete removed photos
        $deletedIds = $request->input('deleted_photo_ids', []);
        if ($deletedIds) {
            $toDelete = $lake->photos()->whereIn('id', $deletedIds)->get();
            foreach ($toDelete as $photo) {
                if (! str_starts_with($photo->path, 'http')) {
                    Storage::disk('public')->delete($photo->path);
                }
                $photo->delete();
            }
        }

        // Append new photos
        if ($request->hasFile('photos')) {
            $maxSort = (int) $lake->photos()->max('sort_order');
            foreach ($request->file('photos') as $index => $file) {
                $path = $file->store('lakes', 'public');
                LakePhoto::create([
                    'lake_id' => $lake->id,
                    'path' => $path,
                    'is_primary' => false,
                    'sort_order' => $maxSort + $index + 1,
                ]);
            }
        }

        // Ensure a primary photo exists
        $lake->load('photos');
        if ($lake->photos->isNotEmpty() && ! $lake->photos->contains('is_primary', true)) {
            $lake->photos->first()->update(['is_primary' => true]);
        }

        return response()->json(['lake' => $this->lakeRow($lake)]);
    }

    public function deleteLake(Lake $lake): JsonResponse
    {
        // Detach catches so user content survives (FK would cascade-delete them)
        CatchRecord::where('lake_id', $lake->id)->update(['lake_id' => null]);

        foreach ($lake->photos as $photo) {
            if (! str_starts_with($photo->path, 'http')) {
                Storage::disk('public')->delete($photo->path);
            }
        }

        $lake->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validateLake(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'price' => 'nullable|numeric|min:0',
            'region' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'fish_species' => 'nullable|string|max:255',
            'area_ha' => 'nullable|integer|min:0',
            'max_depth_m' => 'nullable|integer|min:0',
            'permit_required' => 'boolean',
            'admin_name' => 'nullable|string|max:255',
            'admin_phone' => 'nullable|string|max:255',
            'admin_website' => 'nullable|url|max:255',
            'rules' => 'nullable|string',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|max:5120',
            'deleted_photo_ids' => 'nullable|array',
            'deleted_photo_ids.*' => 'integer',
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Lake::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function lakeRow(Lake $lake): array
    {
        $primary = $lake->photos()->where('is_primary', true)->first()
            ?? $lake->photos()->orderBy('sort_order')->first();

        return [
            'id' => $lake->id,
            'name' => $lake->name,
            'slug' => $lake->slug,
            'region' => $lake->region,
            'latitude' => $lake->latitude,
            'longitude' => $lake->longitude,
            'price' => $lake->price,
            'photo_url' => $primary
                ? (str_starts_with($primary->path, 'http') ? $primary->path : asset('storage/'.$primary->path))
                : null,
            'created_at' => $lake->created_at->toDateString(),
        ];
    }

    public function users(Request $request): JsonResponse
    {
        $users = User::withCount('catchRecords')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar_url' => $u->avatar_url,
                'is_admin' => $u->is_admin,
                'is_blocked' => $u->is_blocked,
                'catches_count' => $u->catch_records_count,
                'created_at' => $u->created_at->toDateString(),
            ]),
            'total' => $users->total(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
        ]);
    }

    public function blockUser(User $user, Request $request): JsonResponse
    {
        if ($user->is_admin) {
            return response()->json(['message' => 'Cannot block an admin'], 422);
        }

        $user->is_blocked = true;
        $user->save();

        return response()->json(['is_blocked' => true]);
    }

    public function unblockUser(User $user): JsonResponse
    {
        $user->is_blocked = false;
        $user->save();

        return response()->json(['is_blocked' => false]);
    }

    public function catches(Request $request): JsonResponse
    {
        $catches = CatchRecord::with('user:id,name,avatar_url', 'lake:id,name,slug')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $catches->map(fn (CatchRecord $c) => [
                'id' => $c->id,
                'type' => $c->type ?? 'catch',
                'fish_name' => $c->fish_name,
                'notes' => $c->notes,
                'photo_url' => $c->photo ? asset('storage/'.$c->photo) : null,
                'user' => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name, 'avatar_url' => $c->user->avatar_url] : null,
                'lake' => $c->lake ? ['id' => $c->lake->id, 'name' => $c->lake->name] : null,
                'created_at' => $c->created_at->toDateString(),
            ]),
            'total' => $catches->total(),
            'current_page' => $catches->currentPage(),
            'last_page' => $catches->lastPage(),
        ]);
    }

    public function deleteCatch(CatchRecord $catchRecord): JsonResponse
    {
        if ($catchRecord->photo) {
            Storage::disk('public')->delete($catchRecord->photo);
        }

        $catchRecord->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Bulk delete. Rows are fetched first so photo files get removed too —
     * a plain mass ->delete() would leave orphaned files on disk.
     * Likes/comments/activities cascade via their FKs.
     */
    public function deleteCatches(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer',
        ]);

        $records = CatchRecord::whereIn('id', $validated['ids'])->get();

        foreach ($records as $record) {
            if ($record->photo && ! str_starts_with($record->photo, 'http')) {
                Storage::disk('public')->delete($record->photo);
            }
            $record->delete();
        }

        return response()->json(['deleted' => $records->count()]);
    }
}
