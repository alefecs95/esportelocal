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
        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained()->onDelete('cascade');
            
            // Mandante e Visitante (Equipe ou Atleta)
            // Não usamos constrained() aqui pois pode referenciar tabelas diferentes (equipes ou atletas)
            $table->unsignedBigInteger('mandante_id')->nullable(); 
            $table->unsignedBigInteger('visitante_id')->nullable();
            
            $table->integer('placar_mandante')->nullable();
            $table->integer('placar_visitante')->nullable();
            
            $table->dateTime('data_jogo')->nullable();
            $table->string('local')->nullable();
            
            $table->enum('status', [
                'agendada',
                'em_andamento',
                'finalizada'
            ])->default('agendada');

            // Controle de mata-mata
            $table->integer('rodada')->nullable(); // 1, 2, 3...
            $table->string('chave')->nullable(); // Identificador da chave/jogo para montar bracket (ex: 1_1, 1_2)
            $table->unsignedBigInteger('proxima_partida_id')->nullable(); // ID da partida onde o vencedor irá
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partidas');
    }
};
