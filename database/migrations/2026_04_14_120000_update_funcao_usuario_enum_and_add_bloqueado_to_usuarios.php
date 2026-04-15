<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enum changes require statements to be visible before using new labels (PostgreSQL).
     */
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement("ALTER TYPE funcao_usuario ADD VALUE IF NOT EXISTS 'user'");
        DB::statement("ALTER TYPE funcao_usuario RENAME VALUE 'admin' TO 'administrador'");

        Schema::table('usuarios', function (Blueprint $table): void {
            $table->boolean('bloqueado')->default(false);
        });

        DB::statement("ALTER TABLE usuarios ALTER COLUMN funcao SET DEFAULT 'user'");
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table): void {
            $table->dropColumn('bloqueado');
        });

        DB::statement("UPDATE usuarios SET funcao = 'brigadista' WHERE funcao = 'user'");
        DB::statement("ALTER TABLE usuarios ALTER COLUMN funcao SET DEFAULT 'brigadista'");
        DB::statement("ALTER TYPE funcao_usuario RENAME VALUE 'administrador' TO 'admin'");
    }
};
