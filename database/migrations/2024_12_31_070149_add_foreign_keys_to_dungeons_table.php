<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToDungeonsTable extends Migration
{
    public function up()
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->foreign('dungeon_setting_id')
                ->references('id')
                ->on('dungeon_settings')
                ->onDelete('cascade');

            $table->foreign('dungeon_type_id')
                ->references('id')
                ->on('dungeon_types')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('dungeons', function (Blueprint $table) {
            $table->dropForeign(['dungeon_setting_id']);
            $table->dropForeign(['dungeon_type_id']);
        });
    }
}
