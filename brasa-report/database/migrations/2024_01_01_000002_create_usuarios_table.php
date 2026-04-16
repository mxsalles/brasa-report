<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP TYPE IF EXISTS funcao_usuario CASCADE');
        DB::statement("CREATE TYPE funcao_usuario AS ENUM ('brigadista', 'gestor', 'admin')");

        Schema::create('usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nome', 150);
            $table->string('email', 255);
            $table->char('cpf', 11);
            $table->string('senha_hash', 255);
            $table->uuid('brigada_id')->nullable();
            $table->timestampTz('criado_em')->default(DB::raw('NOW()'));
            $table->timestampTz('atualizado_em')->default(DB::raw('NOW()'));

            $table->unique('email', 'uq_usuarios_email');
            $table->unique('cpf', 'uq_usuarios_cpf');

            $table->foreign('brigada_id', 'fk_usuario_brigada')
                ->references('id')->on('brigadas')
                ->onDelete('set null');
        });

        DB::statement("ALTER TABLE usuarios ADD COLUMN funcao funcao_usuario NOT NULL DEFAULT 'brigadista'");

        DB::statement('
            CREATE OR REPLACE FUNCTION set_atualizado_em() RETURNS TRIGGER AS $$
            BEGIN
                NEW.atualizado_em = NOW();
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
        ');

        DB::statement('
            CREATE TRIGGER trg_usuarios_atualizado_em
            BEFORE UPDATE ON usuarios
            FOR EACH ROW EXECUTE FUNCTION set_atualizado_em()
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_usuarios_atualizado_em ON usuarios');
        Schema::dropIfExists('usuarios');
        DB::statement('DROP FUNCTION IF EXISTS set_atualizado_em()');
        DB::statement('DROP TYPE IF EXISTS funcao_usuario');
    }
};
