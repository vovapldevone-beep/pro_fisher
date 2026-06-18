<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('catches', function (Blueprint $table) {
            $table->foreignId('lake_id')->nullable()->change();
            $table->string('fish_name')->nullable()->change();
            $table->date('caught_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('catches', function (Blueprint $table) {
            $table->foreignId('lake_id')->nullable(false)->change();
            $table->string('fish_name')->nullable(false)->change();
            $table->date('caught_at')->nullable(false)->change();
        });
    }
};
