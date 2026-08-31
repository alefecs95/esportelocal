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
        Schema::table('eventos_partida', function (Blueprint $table) {
            // Adicionar campo para jogador substituído
            $table->foreignId('jogador_saiu_id')->nullable()->after('jogador_id')->constrained('atletas')->onDelete('cascade');
            
            // Alterar ENUM para aceitar novos tipos (SUBSTITUICAO)
            // MySQL não permite ALTER TABLE CHANGE COLUMN enum facilmente sem redefinir tudo
            // Vamos mudar para VARCHAR para flexibilidade
            // Primeiro remove o enum, depois cria como string
        });

        // Truque para mudar ENUM para VARCHAR no MySQL/MariaDB
        DB::statement("ALTER TABLE eventos_partida MODIFY COLUMN tipo_evento VARCHAR(50) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventos_partida', function (Blueprint $table) {
            $table->dropForeign(['jogador_saiu_id']);
            $table->dropColumn('jogador_saiu_id');
        });

        // Reverter para ENUM restrito (CUIDADO: Pode falhar se tiver dados fora do enum)
        // DB::statement("ALTER TABLE eventos_partida MODIFY COLUMN tipo_evento ENUM('GOL', 'CARTAO_AMARELO', 'CARTAO_VERMELHO') NOT NULL");
    }
};
