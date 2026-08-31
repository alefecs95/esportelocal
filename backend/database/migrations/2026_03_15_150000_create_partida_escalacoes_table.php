<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partida_escalacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prefeitura_id')->constrained('prefeituras')->cascadeOnDelete();
            $table->foreignId('partida_id')->constrained('partidas')->cascadeOnDelete();
            $table->foreignId('equipe_id')->constrained('equipes')->cascadeOnDelete();
            $table->foreignId('atleta_id')->constrained('atletas')->cascadeOnDelete();
            $table->boolean('titular')->default(false);
            $table->boolean('capitao')->default(false);
            $table->unsignedTinyInteger('ordem')->nullable();
            $table->timestamps();

            $table->unique(['partida_id', 'equipe_id', 'atleta_id'], 'uniq_partida_equipe_atleta_escalado');
            $table->index(['partida_id', 'equipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partida_escalacoes');
    }
};
