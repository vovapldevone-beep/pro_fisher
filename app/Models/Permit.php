<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'lake_id',
    'duration_days',
    'expires_at',
    'status',
])]
class Permit extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lake(): BelongsTo
    {
        return $this->belongsTo(Lake::class);
    }
}
