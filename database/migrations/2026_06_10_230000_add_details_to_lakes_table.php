<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lakes', function (Blueprint $table) {
            $table->string('fish_species')->nullable()->after('description');
            $table->unsignedInteger('area_ha')->nullable()->after('fish_species');
            $table->unsignedSmallInteger('max_depth_m')->nullable()->after('area_ha');
            $table->boolean('permit_required')->default(true)->after('max_depth_m');
            $table->string('admin_name')->nullable()->after('permit_required');
            $table->string('admin_phone')->nullable()->after('admin_name');
            $table->string('admin_website')->nullable()->after('admin_phone');
            $table->unsignedInteger('reviews_count')->default(0)->after('rating');
            $table->text('rules')->nullable()->after('admin_website');
        });
    }

    public function down(): void
    {
        Schema::table('lakes', function (Blueprint $table) {
            $table->dropColumn([
                'fish_species',
                'area_ha',
                'max_depth_m',
                'permit_required',
                'admin_name',
                'admin_phone',
                'admin_website',
                'reviews_count',
                'rules',
            ]);
        });
    }
};
