<?php

namespace App\Policies;

use App\Models\CatchRecord;
use App\Models\User;

class CatchRecordPolicy
{
    public function update(User $user, CatchRecord $catchRecord): bool
    {
        return (int) $user->id === (int) $catchRecord->user_id;
    }

    public function delete(User $user, CatchRecord $catchRecord): bool
    {
        return (int) $user->id === (int) $catchRecord->user_id;
    }
}
