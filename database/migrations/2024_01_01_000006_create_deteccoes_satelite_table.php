<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deteccoes_satelite', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestampTz('detectado_em');
            $table->decimal('confianca', 5, 2);
            $table->string('fonte', 100)->default('NASA FIRMS');

            $table->index(['latitude', 'longitude'], 'idx_deteccoes_coords');
        });

        DB::statement('ALTER TABLE deteccoes_satelite ADD CONSTRAINT chk_confianca CHECK (confianca BETWEEN 0 AND 100)');
        DB::statement('CREATE INDEX idx_deteccoes_detectado_em ON deteccoes_satelite(detectado_em DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_deteccoes_detectado_em');
        Schema::dropIfExists('deteccoes_satelite');
    }
};
