<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP TYPE IF EXISTS tipo_alerta CASCADE');
        DB::statement("CREATE TYPE tipo_alerta AS ENUM ('temperatura_alta', 'umidade_baixa', 'fogo_detectado', 'proximidade_local_critico')");

        Schema::create('alertas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('mensagem');
            $table->uuid('origem_id');
            $table->string('origem_tabela', 100);
            $table->timestampTz('enviado_em')->default(DB::raw('NOW()'));
            $table->boolean('entregue')->default(false);

            $table->index(['origem_tabela', 'origem_id'], 'idx_alertas_origem');
        });

        DB::statement('ALTER TABLE alertas ADD COLUMN tipo tipo_alerta NOT NULL');

        DB::statement('CREATE INDEX idx_alertas_enviado_em ON alertas(enviado_em DESC)');
        DB::statement('CREATE INDEX idx_alertas_entregue ON alertas(entregue) WHERE entregue = FALSE');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_alertas_entregue');
        DB::statement('DROP INDEX IF EXISTS idx_alertas_enviado_em');
        Schema::dropIfExists('alertas');
        DB::statement('DROP TYPE IF EXISTS tipo_alerta');
    }
};
