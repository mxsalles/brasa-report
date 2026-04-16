<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brigadas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nome', 150);
            $table->string('tipo', 100);
            $table->decimal('latitude_atual', 10, 7)->nullable();
            $table->decimal('longitude_atual', 10, 7)->nullable();
            $table->boolean('disponivel')->default(true);

            $table->index(['latitude_atual', 'longitude_atual'], 'idx_brigadas_coords');
        });

        DB::statement('CREATE INDEX idx_brigadas_disponivel ON brigadas(disponivel) WHERE disponivel = TRUE');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_brigadas_disponivel');
        Schema::dropIfExists('brigadas');
    }
};
