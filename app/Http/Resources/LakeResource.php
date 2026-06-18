<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LakeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'price' => $this->price,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'fish_species' => $this->fish_species,
            'area_ha' => $this->area_ha,
            'max_depth_m' => $this->max_depth_m,
            'permit_required' => $this->permit_required,
            'admin_name' => $this->admin_name,
            'admin_phone' => $this->admin_phone,
            'admin_website' => $this->admin_website,
            'rules' => $this->rules,
            'region' => $this->region,
            'thumbnail_url' => $this->when(
                $this->relationLoaded('photos'),
                fn () => $this->photos->first()
                    ? (str_starts_with($this->photos->first()->path, 'http')
                        ? $this->photos->first()->path
                        : asset('storage/'.$this->photos->first()->path))
                    : null
            ),
            'address' => $this->address,
            'photos' => LakePhotoResource::collection($this->whenLoaded('photos')),
            'recent_catches' => CatchResource::collection($this->whenLoaded('recentCatches')),
            'reviews' => LakeReviewResource::collection($this->whenLoaded('reviews')),
            'catch_stats' => $this->when(isset($this->catch_stats), $this->catch_stats),
            'permit_options' => $this->when(isset($this->permit_options), $this->permit_options),
        ];
    }
}
