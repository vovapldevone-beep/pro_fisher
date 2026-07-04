<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatchRecord;
use App\Models\CatchComment;
use App\Models\Lake;
use App\Models\LakePhoto;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'users_count'    => User::count(),
            'blocked_count'  => User::where('is_blocked', true)->count(),
            'catches_count'  => CatchRecord::where('type', 'catch')->count(),
            'posts_count'    => CatchRecord::where('type', 'post')->count(),
            'likes_count'    => PostLike::count(),
            'comments_count' => CatchComment::count(),
            'lakes_count'    => Lake::count(),
        ]);
    }

    public function lakes(Request $request): JsonResponse
    {
        $lakes = Lake::with(['photos' => fn ($q) => $q->where('is_primary', true)->limit(1)])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $lakes->map(fn (Lake $l) => [
                'id'        => $l->id,
                'name'      => $l->name,
                'slug'      => $l->slug,
                'region'    => $l->region,
                'latitude'  => $l->latitude,
                'longitude' => $l->longitude,
                'price'     => $l->price,
                'photo_url' => $l->photos->first()
                    ? (str_starts_with($l->photos->first()->path, 'http')
                        ? $l->photos->first()->path
                        : asset('storage/'.$l->photos->first()->path))
                    : null,
                'created_at'=> $l->created_at->toDateString(),
            ]),
            'total'        => $lakes->total(),
            'current_page' => $lakes->currentPage(),
            'last_page'    => $lakes->lastPage(),
        ]);
    }

    public function storeLake(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'latitude'        => 'required|numeric|between:-90,90',
            'longitude'       => 'required|numeric|between:-180,180',
            'price'           => 'nullable|numeric|min:0',
            'region'          => 'nullable|string|max:255',
            'address'         => 'nullable|string|max:255',
            'fish_species'    => 'nullable|string|max:255',
            'area_ha'         => 'nullable|integer|min:0',
            'max_depth_m'     => 'nullable|integer|min:0',
            'permit_required' => 'boolean',
            'admin_name'      => 'nullable|string|max:255',
            'admin_phone'     => 'nullable|string|max:255',
            'admin_website'   => 'nullable|url|max:255',
            'rules'           => 'nullable|string',
            'photos'          => 'nullable|array|max:10',
            'photos.*'        => 'image|max:5120',
        ]);

        $base = Str::slug($validated['name']);
        $slug = $base;
        $i = 1;
        while (Lake::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        $validated['slug'] = $slug;

        $lake = Lake::create($validated);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $file) {
                $path = $file->store('lakes', 'public');
                LakePhoto::create([
                    'lake_id'    => $lake->id,
                    'path'       => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }

        $photoUrl = $lake->photos()->orderBy('sort_order')->first()?->path;

        return response()->json([
            'lake' => [
                'id'        => $lake->id,
                'name'      => $lake->name,
                'slug'      => $lake->slug,
                'region'    => $lake->region,
                'latitude'  => $lake->latitude,
                'longitude' => $lake->longitude,
                'price'     => $lake->price,
                'photo_url' => $photoUrl ? asset('storage/'.$photoUrl) : null,
                'created_at'=> $lake->created_at->toDateString(),
            ],
        ], 201);
    }

    public function users(Request $request): JsonResponse
    {
        $users = User::withCount('catchRecords')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $users->map(fn (User $u) => [
                'id'             => $u->id,
                'name'           => $u->name,
                'email'          => $u->email,
                'avatar_url'     => $u->avatar_url,
                'is_admin'       => $u->is_admin,
                'is_blocked'     => $u->is_blocked,
                'catches_count'  => $u->catch_records_count,
                'created_at'     => $u->created_at->toDateString(),
            ]),
            'total'        => $users->total(),
            'current_page' => $users->currentPage(),
            'last_page'    => $users->lastPage(),
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
                'id'         => $c->id,
                'type'       => $c->type ?? 'catch',
                'fish_name'  => $c->fish_name,
                'notes'      => $c->notes,
                'photo_url'  => $c->photo ? asset('storage/'.$c->photo) : null,
                'user'       => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name, 'avatar_url' => $c->user->avatar_url] : null,
                'lake'       => $c->lake ? ['id' => $c->lake->id, 'name' => $c->lake->name] : null,
                'created_at' => $c->created_at->toDateString(),
            ]),
            'total'        => $catches->total(),
            'current_page' => $catches->currentPage(),
            'last_page'    => $catches->lastPage(),
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
}
