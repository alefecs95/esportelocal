<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campeonatos', function (Blueprint $table) {
            $table->boolean('suspensao_automatica_ativa')->default(true)->after('rodada_limite_inscricao_atletas');
            $table->unsignedTinyInteger('cartoes_amarelos_para_suspensao')->default(2)->after('suspensao_automatica_ativa');
            $table->boolean('cartao_vermelho_suspende')->default(true)->after('cartoes_amarelos_para_suspensao');
        });
    }

    public function down(): void
    {
        Schema::table('campeonatos', function (Blueprint $table) {
            $table->dropColumn([
                'suspensao_automatica_ativa',
                'cartoes_amarelos_para_suspensao',
                'cartao_vermelho_suspende',
            ]);
        });
    }
};
