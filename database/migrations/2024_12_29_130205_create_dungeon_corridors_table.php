<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDungeonCorridorsTable extends Migration
{
    public function up()
    {
        Schema::create('dungeon_corridors', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedBigInteger('dungeon_id'); // Foreign key for the dungeon
            $table->json('cells'); // JSON column to store corridor cells (coordinates of 'C' tiles)
            $table->timestamps(); // Created at / Updated at timestamps

            // Foreign key constraint to the `dungeons` table
            $table->foreign('dungeon_id')->references('id')->on('dungeons')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dungeon_corridors');
    }
}
