<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP TYPE IF EXISTS nivel_risco CASCADE');
        DB::statement('DROP TYPE IF EXISTS status_incendio CASCADE');
        DB::statement("CREATE TYPE nivel_risco AS ENUM ('alto', 'medio', 'baixo')");
        DB::statement("CREATE TYPE status_incendio AS ENUM ('ativo', 'contido', 'resolvido')");

        Schema::create('incendios', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestampTz('detectado_em')->default(DB::raw('NOW()'));
            $table->uuid('usuario_id');
            $table->uuid('area_id');
            $table->uuid('local_critico_id')->nullable();
            $table->uuid('deteccao_satelite_id')->nullable();

            $table->index(['latitude', 'longitude'], 'idx_incendios_coords');
            $table->index('area_id', 'idx_incendios_area');

            $table->foreign('usuario_id', 'fk_incendio_usuario')
                ->references('id')->on('usuarios')
                ->onDelete('restrict');

            $table->foreign('area_id', 'fk_incendio_area')
                ->references('id')->on('areas_monitoradas')
                ->onDelete('restrict');

            $table->foreign('local_critico_id', 'fk_incendio_local')
                ->references('id')->on('locais_criticos')
                ->onDelete('set null');

            $table->foreign('deteccao_satelite_id', 'fk_incendio_satelite')
                ->references('id')->on('deteccoes_satelite')
                ->onDelete('set null');
        });

        DB::statement("ALTER TABLE incendios ADD COLUMN nivel_risco nivel_risco NOT NULL DEFAULT 'alto'");
        DB::statement("ALTER TABLE incendios ADD COLUMN status status_incendio NOT NULL DEFAULT 'ativo'");

        DB::statement('CREATE INDEX idx_incendios_status ON incendios(status)');
        DB::statement('CREATE INDEX idx_incendios_nivel_risco ON incendios(nivel_risco)');
        DB::statement('CREATE INDEX idx_incendios_detectado_em ON incendios(detectado_em DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_incendios_detectado_em');
        DB::statement('DROP INDEX IF EXISTS idx_incendios_nivel_risco');
        DB::statement('DROP INDEX IF EXISTS idx_incendios_status');
        Schema::dropIfExists('incendios');
        DB::statement('DROP TYPE IF EXISTS status_incendio');
        DB::statement('DROP TYPE IF EXISTS nivel_risco');
    }
};
