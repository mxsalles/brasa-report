<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despachos_brigada', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('incendio_id');
            $table->uuid('brigada_id');
            $table->timestampTz('despachado_em')->default(DB::raw('NOW()'));
            $table->timestampTz('chegada_em')->nullable();
            $table->timestampTz('finalizado_em')->nullable();
            $table->text('observacoes')->nullable();

            $table->index('incendio_id', 'idx_despachos_incendio');

            $table->foreign('incendio_id', 'fk_despacho_incendio')
                ->references('id')->on('incendios')
                ->onDelete('cascade');

            $table->foreign('brigada_id', 'fk_despacho_brigada')
                ->references('id')->on('brigadas')
                ->onDelete('restrict');
        });

        DB::statement('
            ALTER TABLE despachos_brigada ADD CONSTRAINT chk_despacho_timeline CHECK (
                (chegada_em IS NULL OR chegada_em >= despachado_em) AND
                (finalizado_em IS NULL OR finalizado_em >= chegada_em)
            )
        ');

        DB::statement('CREATE INDEX idx_despachos_finalizado ON despachos_brigada(finalizado_em) WHERE finalizado_em IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_despachos_finalizado');
        DB::statement('ALTER TABLE despachos_brigada DROP CONSTRAINT IF EXISTS chk_despacho_timeline');
        Schema::dropIfExists('despachos_brigada');
    }
};
