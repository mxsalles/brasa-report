<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas_monitoradas', function (Blueprint $table) {
            $table->dropColumn('geometria_wkt');
            $table->longText('geometria_geojson')->nullable()->after('caminho_geopackage');
        });
    }

    public function down(): void
    {
        Schema::table('areas_monitoradas', function (Blueprint $table) {
            $table->dropColumn('geometria_geojson');
            $table->text('geometria_wkt')->nullable()->after('caminho_geopackage');
        });
    }
};
