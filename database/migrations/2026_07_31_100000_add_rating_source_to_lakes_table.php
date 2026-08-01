<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where a lake's `rating` came from. NULL means it is ours; 'google' marks
     * a score imported from Google Maps, which the UI has to label as such
     * rather than pass off as a ProFisher rating.
     */
    public function up(): void
    {
        Schema::table('lakes', function (Blueprint $table) {
            $table->string('rating_source', 20)->nullable()->after('reviews_count');
        });
    }

    public function down(): void
    {
        Schema::table('lakes', function (Blueprint $table) {
            $table->dropColumn('rating_source');
        });
    }
};
