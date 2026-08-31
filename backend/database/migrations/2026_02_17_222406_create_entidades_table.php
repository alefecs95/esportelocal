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
        Schema::create('entidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('sigla')->nullable();
            $table->date('data_fundacao')->nullable();
            $table->string('responsavel')->nullable();
            $table->string('contato')->nullable();
            $table->string('logo')->nullable(); // Caminho da imagem
            $table->string('cidade')->nullable();
            $table->string('bairro')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entidades');
    }
};
