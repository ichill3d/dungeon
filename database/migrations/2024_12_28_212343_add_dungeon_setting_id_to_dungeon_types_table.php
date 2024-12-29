<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDungeonSettingIdToDungeonTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_types', function (Blueprint $table) {
            // Add the new column
            $table->unsignedBigInteger('dungeon_setting_id')->nullable();

            // Add the foreign key constraint
            $table->foreign('dungeon_setting_id')
                ->references('id')->on('dungeon_settings')
                ->onDelete('cascade'); // Adjust the delete behavior as necessary
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_types', function (Blueprint $table) {
            // Drop the foreign key and column
            $table->dropForeign(['dungeon_setting_id']);
            $table->dropColumn('dungeon_setting_id');
        });
    }
}
