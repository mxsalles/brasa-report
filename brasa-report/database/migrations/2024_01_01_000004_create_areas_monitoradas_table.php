<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas_monitoradas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nome', 150);
            $table->string('caminho_geopackage', 500)->nullable();
            $table->text('geometria_wkt')->nullable();
            $table->timestampTz('importado_em')->default(DB::raw('NOW()'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas_monitoradas');
    }
};
