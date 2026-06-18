<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['catch_id', 'user_id', 'body'])]
class CatchComment extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function catch(): BelongsTo
    {
        return $this->belongsTo(CatchRecord::class, 'catch_id');
    }
}
