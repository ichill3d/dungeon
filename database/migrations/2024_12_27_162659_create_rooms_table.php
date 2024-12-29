<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('dungeon_id')->constrained('dungeons')->cascadeOnDelete(); // Foreign key to dungeons
            $table->string('name'); // Room name
            $table->text('description')->nullable(); // Room description
            $table->enum('type', ['empty', 'monster', 'trap', 'loot'])->default('empty'); // Room type
            $table->boolean('is_explored')->default(false); // Exploration status
            $table->json('metadata')->nullable(); // Additional data (e.g., items, NPCs)
            $table->timestamps(); // Created at, Updated at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
