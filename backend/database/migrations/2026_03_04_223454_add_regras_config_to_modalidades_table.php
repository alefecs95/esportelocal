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
        Schema::table('modalidades', function (Blueprint $table) {
            $table->json('regras_config')->nullable()->after('descricao');
            // Estrutura esperada do JSON:
            // {
            //   "tipo_pontuacao": "pontos" | "tempo" | "distancia",
            //   "campos_categoria": ["peso", "faixa", "idade"],
            //   "campos_resultado": ["gols", "sets"],
            //   "permite_empate": true/false
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modalidades', function (Blueprint $table) {
            $table->dropColumn('regras_config');
        });
    }
};
