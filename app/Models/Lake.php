<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'latitude',
    'longitude',
    'price',
    'rating',
    'reviews_count',
    'fish_species',
    'area_ha',
    'max_depth_m',
    'permit_required',
    'admin_name',
    'admin_phone',
    'admin_website',
    'rules',
    'region',
    'address',
])]
class Lake extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'price' => 'decimal:2',
            'rating' => 'decimal:1',
            'permit_required' => 'boolean',
        ];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(LakePhoto::class)->orderBy('sort_order');
    }

    public function catchRecords(): HasMany
    {
        return $this->hasMany(CatchRecord::class);
    }

    public function recentCatches(): HasMany
    {
        return $this->hasMany(CatchRecord::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(LakeReview::class)->latest();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
