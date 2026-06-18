<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lake_id',
    'path',
    'is_primary',
    'sort_order',
])]
class LakePhoto extends Model
{
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function lake(): BelongsTo
    {
        return $this->belongsTo(Lake::class);
    }
}
