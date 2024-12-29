<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dungeons', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name'); // Dungeon name
            $table->text('description')->nullable(); // Dungeon description
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete(); // Registered user (nullable for guests)
            $table->string('session_id')->nullable(); // Guest session identifier
            $table->json('metadata')->nullable(); // Additional dungeon data (e.g., JSON for settings)
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dungeons');
    }
};
