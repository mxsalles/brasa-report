<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('brigadas', function (Blueprint $table): void {
            $table->softDeletesTz('deleted_at');
        });

        Schema::table('incendios', function (Blueprint $table): void {
            $table->softDeletesTz('deleted_at');
        });

        Schema::table('usuarios', function (Blueprint $table): void {
            $table->softDeletesTz('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brigadas', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('incendios', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('usuarios', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
