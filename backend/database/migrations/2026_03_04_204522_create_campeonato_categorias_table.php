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
        Schema::create('campeonato_categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prefeitura_id')->constrained()->onDelete('cascade');
            $table->foreignId('campeonato_id')->constrained()->onDelete('cascade');
            
            $table->string('nome'); // Ex: "Sub-20", "5km Geral", "Pesado (>100kg)"
            $table->text('descricao')->nullable();
            
            // Regras de Elegibilidade
            $table->enum('genero', ['M', 'F', 'Misto'])->default('Misto');
            $table->integer('idade_min')->nullable();
            $table->integer('idade_max')->nullable();
            $table->decimal('peso_min', 8, 2)->nullable();
            $table->decimal('peso_max', 8, 2)->nullable();
            
            // Outros
            $table->string('distancia')->nullable(); // Ex: "5km", "100m" (pode ser string para flexibilidade)
            $table->decimal('valor_inscricao', 10, 2)->nullable(); // Override do valor geral se necessário
            $table->integer('vagas_limite')->nullable(); // Limite específico da categoria
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campeonato_categorias');
    }
};
