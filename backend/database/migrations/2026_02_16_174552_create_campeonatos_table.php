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
        Schema::create('campeonatos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->foreignId('modalidade_id')->constrained()->onDelete('cascade');
            $table->string('categoria'); // Ex: Sub-15, Adulto, Master
            $table->enum('genero', ['masculino', 'feminino', 'misto']);
            $table->enum('tipo_competicao', ['individual', 'equipe']);
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->integer('limite_participantes')->nullable();
            $table->decimal('valor_inscricao', 10, 2)->nullable();
            $table->text('regulamento')->nullable();
            $table->enum('status', ['planejado', 'aberto', 'em_andamento', 'finalizado', 'cancelado'])->default('planejado');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campeonatos');
    }
};
