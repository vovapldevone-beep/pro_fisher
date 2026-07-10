<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ties a "catch" activity to the record it describes, so deleting the catch
     * removes the feed entry with it. Nullable because `following`/`follower`
     * activities have no catch.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('catch_id')
                ->nullable()
                ->after('user_id')
                ->constrained('catches')
                ->cascadeOnDelete();
        });

        $this->backfillExistingActivities();
    }

    /**
     * Existing rows predate the column. Match each one to its catch by the fields
     * the feed already stores, then drop the leftovers — those describe catches
     * that were deleted before the foreign key existed.
     */
    private function backfillExistingActivities(): void
    {
        $activities = DB::table('activities')->where('type', 'catch')->get();
        $claimed = [];

        foreach ($activities as $activity) {
            $data = json_decode($activity->data, true) ?? [];

            $catch = DB::table('catches')
                ->where('user_id', $activity->user_id)
                ->where('type', 'catch')
                ->where('fish_name', $data['fish_name'] ?? null)
                ->when(isset($data['weight']), fn ($q) => $q->where('weight', $data['weight']))
                ->whereNotIn('id', $claimed)
                ->orderBy('id')
                ->first();

            if ($catch) {
                $claimed[] = $catch->id;
                DB::table('activities')->where('id', $activity->id)->update(['catch_id' => $catch->id]);
            }
        }

        DB::table('activities')->where('type', 'catch')->whereNull('catch_id')->delete();
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('catch_id');
        });
    }
};
