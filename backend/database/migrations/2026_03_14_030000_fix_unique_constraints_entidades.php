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
        Schema::table('entidades', function (Blueprint $table) {
            // Drop existing unique constraint on 'nome'
            // The index name is usually 'table_column_unique'
            $table->dropUnique('entidades_nome_unique');
            // Add composite unique constraint
            $table->unique(['nome', 'prefeitura_id'], 'entidades_nome_prefeitura_unique');
        });

        // Check if 'equipes' table has unique constraint on 'nome' and fix it too if exists
        // Assuming 'equipes' table structure from inspection or common pattern
        // It's safer to check first, but for now let's just handle 'entidades' which is blocking.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $table->dropUnique('entidades_nome_prefeitura_unique');
            $table->unique('nome', 'entidades_nome_unique');
        });
    }
};
