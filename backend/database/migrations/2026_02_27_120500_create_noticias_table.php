<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('noticias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->foreignId('autor_id')->constrained('users')->onDelete('cascade');
            $table->string('titulo');
            $table->longText('conteudo');
            $table->string('imagem_capa')->nullable();
            $table->boolean('publicado')->default(false);
            $table->dateTime('publicado_em')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('prefeitura_id');
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noticias');
    }
};
