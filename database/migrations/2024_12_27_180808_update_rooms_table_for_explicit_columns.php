<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Remove metadata column
            $table->dropColumn('metadata');

            // Add explicit columns for grid placement and dimensions
            $table->integer('x')->default(0)->after('description');
            $table->integer('y')->default(0)->after('x');
            $table->integer('width')->default(3)->after('y');
            $table->integer('height')->default(3)->after('width');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Restore metadata column
            $table->longText('metadata')
                ->collation('utf8mb4_bin')
                ->nullable()
                ->check('json_valid(`metadata`)');

            // Remove added columns
            $table->dropColumn(['x', 'y', 'width', 'height']);
        });
    }
};
