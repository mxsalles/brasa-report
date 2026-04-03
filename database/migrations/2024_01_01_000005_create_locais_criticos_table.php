<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP TYPE IF EXISTS tipo_local_critico CASCADE');
        DB::statement("CREATE TYPE tipo_local_critico AS ENUM ('residencia', 'escola', 'infraestrutura')");

        Schema::create('locais_criticos', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nome', 150);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('descricao')->nullable();

            $table->index(['latitude', 'longitude'], 'idx_locais_criticos_coords');
        });

        DB::statement('ALTER TABLE locais_criticos ADD COLUMN tipo tipo_local_critico NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('locais_criticos');
        DB::statement('DROP TYPE IF EXISTS tipo_local_critico');
    }
};
