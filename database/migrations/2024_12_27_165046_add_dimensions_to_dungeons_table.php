<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->integer('width')->default(50)->after('size');
            $table->integer('height')->default(50)->after('width');
        });
    }

    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn(['width', 'height']);
        });
    }
};
