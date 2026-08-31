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
        Schema::create('ranking_atletas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temporada_id')->constrained();
            $table->foreignId('modalidade_id')->constrained();
            $table->foreignId('atleta_id')->constrained();

            $table->integer('pontos')->default(0);
            $table->integer('participacoes')->default(0);
            $table->integer('titulos')->default(0);
            $table->timestamps();

            $table->unique(['temporada_id', 'modalidade_id', 'atleta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranking_atletas');
    }
};
