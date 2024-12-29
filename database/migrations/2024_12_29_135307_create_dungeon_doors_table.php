<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDungeonDoorsTable extends Migration
{
    public function up()
    {
        Schema::create('dungeon_doors', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedBigInteger('dungeon_id'); // Foreign key for the dungeon
            $table->integer('x'); // X coordinate of the door
            $table->integer('y'); // Y coordinate of the door
            $table->timestamps(); // Created at / Updated at timestamps

            // Foreign key constraint to the `dungeons` table
            $table->foreign('dungeon_id')->references('id')->on('dungeons')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dungeon_doors');
    }
}
