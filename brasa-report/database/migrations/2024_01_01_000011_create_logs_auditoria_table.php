<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('usuario_id')->nullable();
            $table->string('acao', 150);
            $table->string('entidade_tipo', 100);
            $table->uuid('entidade_id')->nullable();
            $table->jsonb('dados_json')->nullable();
            $table->timestampTz('criado_em')->default(DB::raw('NOW()'));
            $table->timestampTz('atualizado_em')->default(DB::raw('NOW()'));

            $table->index(['entidade_tipo', 'entidade_id'], 'idx_logs_entidade');

            $table->foreign('usuario_id', 'fk_log_usuario')
                ->references('id')->on('usuarios')
                ->onDelete('set null');
        });

        DB::statement('CREATE INDEX idx_logs_criado_em ON logs_auditoria(criado_em DESC)');
        DB::statement('CREATE INDEX idx_logs_dados_json ON logs_auditoria USING GIN (dados_json)');

        DB::statement('
            CREATE TRIGGER trg_logs_auditoria_atualizado_em
            BEFORE UPDATE ON logs_auditoria
            FOR EACH ROW EXECUTE FUNCTION set_atualizado_em()
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_logs_auditoria_atualizado_em ON logs_auditoria');
        DB::statement('DROP INDEX IF EXISTS idx_logs_dados_json');
        DB::statement('DROP INDEX IF EXISTS idx_logs_criado_em');
        Schema::dropIfExists('logs_auditoria');
    }
};
