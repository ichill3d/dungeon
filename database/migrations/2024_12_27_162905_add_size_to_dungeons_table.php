<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->enum('size', ['tiny', 'small', 'medium', 'large', 'enormous'])
                ->default('medium')
                ->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};
