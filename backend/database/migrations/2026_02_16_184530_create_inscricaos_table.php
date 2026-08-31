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
        Schema::create('inscricoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atleta_id')->constrained('atletas')->onDelete('cascade');
            $table->foreignId('campeonato_id')->constrained('campeonatos')->onDelete('cascade');
            
            $table->enum('status', [
                'pendente', 
                'confirmada', 
                'cancelada', 
                'rejeitada'
            ])->default('pendente');
            
            $table->enum('status_pagamento', [
                'nao_pago', 
                'pago', 
                'isento'
            ])->default('nao_pago');
            
            $table->decimal('valor_pago', 10, 2)->nullable();
            $table->date('data_pagamento')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscricoes');
    }
};
