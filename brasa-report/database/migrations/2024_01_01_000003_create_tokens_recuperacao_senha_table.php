<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokens_recuperacao_senha', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('usuario_id');
            $table->string('token', 255);
            $table->timestampTz('expira_em');
            $table->boolean('usado')->default(false);
            $table->timestampTz('criado_em')->default(DB::raw('NOW()'));
            $table->timestampTz('atualizado_em')->default(DB::raw('NOW()'));

            $table->unique('token', 'uq_token');

            $table->foreign('usuario_id', 'fk_token_usuario')
                ->references('id')->on('usuarios')
                ->onDelete('cascade');

            $table->index('expira_em', 'idx_tokens_expira_em');
        });

        DB::statement('
            CREATE TRIGGER trg_tokens_atualizado_em
            BEFORE UPDATE ON tokens_recuperacao_senha
            FOR EACH ROW EXECUTE FUNCTION set_atualizado_em()
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_tokens_atualizado_em ON tokens_recuperacao_senha');
        Schema::dropIfExists('tokens_recuperacao_senha');
    }
};
