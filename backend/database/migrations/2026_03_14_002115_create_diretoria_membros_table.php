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
        Schema::create('diretoria_membros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prefeitura_id')->constrained('prefeituras')->onDelete('cascade');
            $table->string('nome');
            $table->string('funcao');
            $table->string('foto')->nullable();
            $table->integer('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diretoria_membros');
    }
};
