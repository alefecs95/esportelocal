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
        Schema::create('eventos_partida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->onDelete('cascade');
            $table->foreignId('jogador_id')->constrained('atletas')->onDelete('cascade');
            $table->foreignId('time_id')->constrained('equipes')->onDelete('cascade');
            $table->enum('tipo_evento', ['GOL', 'CARTAO_AMARELO', 'CARTAO_VERMELHO']);
            $table->integer('minuto');
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos_partida');
    }
};
