<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'lake_id',
    'type',
    'fish_name',
    'weight',
    'photo',
    'caught_at',
    'notes',
    'location',
])]
class CatchRecord extends Model
{
    protected $table = 'catches';

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'caught_at' => 'date',
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

    public function postLikes(): HasMany
    {
        return $this->hasMany(PostLike::class, 'catch_id');
    }

    public function catchComments(): HasMany
    {
        return $this->hasMany(CatchComment::class, 'catch_id');
    }
}
