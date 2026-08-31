<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpf_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->string('cpf', 11);
            $table->string('nome');
            $table->date('data_nascimento');
            $table->string('sexo', 20);
            $table->json('dados_json')->nullable();
            $table->timestamps();

            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
            $table->unique(['prefeitura_id', 'cpf']);
            $table->index('cpf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpf_cache');
    }
};
