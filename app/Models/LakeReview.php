<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lake_id',
    'author_name',
    'rating',
    'comment',
])]
class LakeReview extends Model
{
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function lake(): BelongsTo
    {
        return $this->belongsTo(Lake::class);
    }
}
