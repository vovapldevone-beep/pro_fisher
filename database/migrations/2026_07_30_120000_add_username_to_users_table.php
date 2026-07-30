<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable so the column can be added before the backfill runs;
            // every code path that creates a user fills it in from then on.
            $table->string('username', 40)->nullable()->unique()->after('name');
        });

        // Existing accounts had no handle — give each one now, oldest first so
        // the plain (suffix-free) name goes to whoever registered earliest.
        User::query()
            ->whereNull('username')
            ->orderBy('id')
            ->each(function (User $user) {
                $user->username = User::generateUsername($user->name);
                $user->save();
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
