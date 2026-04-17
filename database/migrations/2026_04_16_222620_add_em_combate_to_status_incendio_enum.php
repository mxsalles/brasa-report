<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TYPE status_incendio ADD VALUE IF NOT EXISTS 'em_combate' AFTER 'ativo'");
    }

    public function down(): void
    {
        // PostgreSQL does not support removing enum values directly.
    }
};
