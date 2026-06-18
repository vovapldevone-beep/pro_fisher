<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LakeResource;
use App\Models\CatchRecord;
use App\Models\Lake;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LakeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $lakes = Lake::query()
            ->with(['photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->limit(1)])
            ->orderBy('name')
            ->get();

        return LakeResource::collection($lakes);
    }

    public function show(Lake $lake): LakeResource
    {
        $lake->load([
            'photos',
            'reviews' => fn ($query) => $query->limit(5),
            'recentCatches' => fn ($query) => $query
                ->with('user:id,name')
                ->latest('caught_at')
                ->limit(10),
        ]);

        $catchStats = CatchRecord::query()
            ->where('lake_id', $lake->id)
            ->where('caught_at', '>=', now()->subDays(30))
            ->selectRaw('fish_name, count(*) as count')
            ->groupBy('fish_name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'fish_name' => $row->fish_name,
                'count' => (int) $row->count,
            ]);

        $lake->setAttribute('catch_stats', $catchStats);
        $lake->setAttribute('permit_options', $this->buildPermitOptions($lake));

        return new LakeResource($lake);
    }

    private function buildPermitOptions(Lake $lake): array
    {
        $basePrice = (float) ($lake->price ?? 30);

        return [
            ['days' => 1, 'label' => '1 день', 'price' => $basePrice],
            ['days' => 3, 'label' => '3 дні', 'price' => round($basePrice * 2.33, 0)],
            ['days' => 7, 'label' => '7 днів', 'price' => round($basePrice * 4, 0)],
            ['days' => 30, 'label' => '30 днів', 'price' => round($basePrice * 6.67, 0)],
        ];
    }
}
