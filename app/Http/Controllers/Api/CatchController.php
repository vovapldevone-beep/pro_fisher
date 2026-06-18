<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catch\StoreCatchRequest;
use App\Http\Requests\Catch\UpdateCatchRequest;
use App\Http\Resources\CatchResource;
use App\Models\Activity;
use App\Models\CatchRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class CatchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $catches = CatchRecord::query()
            ->where('user_id', $request->user()->id)
            ->with('lake:id,name,slug')
            ->latest('caught_at')
            ->get();

        return CatchResource::collection($catches);
    }

    public function store(StoreCatchRequest $request): CatchResource
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('catches', 'public');
        }

        $catch = CatchRecord::create($data);
        $catch->load('lake:id,name,slug');

        Activity::create([
            'user_id' => $request->user()->id,
            'type' => 'catch',
            'data' => [
                'fish_name' => $catch->fish_name,
                'weight' => $catch->weight,
                'lake_name' => $catch->lake?->name,
            ],
        ]);

        return new CatchResource($catch);
    }

    public function update(UpdateCatchRequest $request, CatchRecord $catchRecord): CatchResource
    {
        $this->authorize('update', $catchRecord);

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($catchRecord->photo) {
                Storage::disk('public')->delete($catchRecord->photo);
            }
            $data['photo'] = $request->file('photo')->store('catches', 'public');
        }

        $catchRecord->update($data);
        $catchRecord->load('lake:id,name,slug');

        return new CatchResource($catchRecord);
    }

    public function destroy(CatchRecord $catchRecord): JsonResponse
    {
        $this->authorize('delete', $catchRecord);

        if ($catchRecord->photo) {
            Storage::disk('public')->delete($catchRecord->photo);
        }

        $catchRecord->delete();

        return response()->json([
            'message' => 'Połów został usunięty.',
        ]);
    }
}
