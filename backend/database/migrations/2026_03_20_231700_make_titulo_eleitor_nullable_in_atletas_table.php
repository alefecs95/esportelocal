<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE atletas MODIFY COLUMN titulo_eleitor VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE atletas SET titulo_eleitor = '' WHERE titulo_eleitor IS NULL");
        DB::statement('ALTER TABLE atletas MODIFY COLUMN titulo_eleitor VARCHAR(255) NOT NULL');
    }
};
