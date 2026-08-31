<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alterar a definição da coluna ENUM para incluir 'corrida'
        // MySQL requer redefinição completa
        DB::statement("ALTER TABLE campeonatos MODIFY COLUMN formato ENUM('mata_mata', 'grupos', 'pontos_corridos', 'corrida') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para o estado anterior (cuidado: pode falhar se houver dados 'corrida')
        // Em um cenário real, deveríamos tratar isso, mas aqui vamos apenas tentar reverter
        DB::statement("UPDATE campeonatos SET formato = NULL WHERE formato = 'corrida'");
        DB::statement("ALTER TABLE campeonatos MODIFY COLUMN formato ENUM('mata_mata', 'grupos', 'pontos_corridos') NULL");
    }
};
