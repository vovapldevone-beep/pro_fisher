<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fish_name' => $this->fish_name,
            'weight' => $this->weight,
            'photo' => $this->photo,
            'photo_url' => $this->photo
                ? (str_starts_with($this->photo, 'http') ? $this->photo : asset('storage/'.$this->photo))
                : null,
            'caught_at' => $this->caught_at?->format('Y-m-d'),
            'type' => $this->type ?? 'catch',
            'notes' => $this->notes,
            'location' => $this->location,
            'lake' => new LakeResource($this->whenLoaded('lake')),
            'user' => $this->when($this->relationLoaded('user'), fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar_url' => $this->user->avatar_url,
            ]),
            'likes_count' => $this->post_likes_count ?? 0,
            'is_liked' => $this->relationLoaded('postLikes') ? $this->postLikes->isNotEmpty() : false,
            'comments_count' => $this->catch_comments_count ?? 0,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
