<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leituras_meteorologicas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('incendio_id');
            $table->decimal('temperatura', 5, 2);
            $table->decimal('umidade', 5, 2);
            $table->decimal('velocidade_vento', 6, 2);
            $table->timestampTz('registrado_em')->default(DB::raw('NOW()'));
            $table->boolean('gera_alerta')->default(false);

            $table->index('incendio_id', 'idx_leituras_incendio');

            $table->foreign('incendio_id', 'fk_leitura_incendio')
                ->references('id')->on('incendios')
                ->onDelete('cascade');
        });

        DB::statement('CREATE INDEX idx_leituras_registrado_em ON leituras_meteorologicas(registrado_em DESC)');
        DB::statement('CREATE INDEX idx_leituras_gera_alerta ON leituras_meteorologicas(gera_alerta) WHERE gera_alerta = TRUE');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_leituras_gera_alerta');
        DB::statement('DROP INDEX IF EXISTS idx_leituras_registrado_em');
        Schema::dropIfExists('leituras_meteorologicas');
    }
};
