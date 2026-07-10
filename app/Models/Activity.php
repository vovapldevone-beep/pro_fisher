<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'catch_id', 'type', 'data'])]
class Activity extends Model
{
    protected $casts = ['data' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function catchRecord(): BelongsTo
    {
        return $this->belongsTo(CatchRecord::class, 'catch_id');
    }
}
