<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incendios', function (Blueprint $table) {
            $table->dropForeign('fk_incendio_area');
            $table->dropIndex('idx_incendios_area');
        });

        DB::statement('ALTER TABLE incendios ALTER COLUMN area_id DROP NOT NULL');

        Schema::table('incendios', function (Blueprint $table) {
            $table->foreign('area_id', 'fk_incendio_area')
                ->references('id')->on('areas_monitoradas')
                ->onDelete('set null');

            $table->index('area_id', 'idx_incendios_area');
        });
    }

    public function down(): void
    {
        Schema::table('incendios', function (Blueprint $table) {
            $table->dropForeign('fk_incendio_area');
            $table->dropIndex('idx_incendios_area');
        });

        DB::statement('UPDATE incendios SET area_id = NULL WHERE area_id IS NOT NULL AND area_id NOT IN (SELECT id FROM areas_monitoradas)');
        DB::statement('ALTER TABLE incendios ALTER COLUMN area_id SET NOT NULL');

        Schema::table('incendios', function (Blueprint $table) {
            $table->foreign('area_id', 'fk_incendio_area')
                ->references('id')->on('areas_monitoradas')
                ->onDelete('restrict');

            $table->index('area_id', 'idx_incendios_area');
        });
    }
};
