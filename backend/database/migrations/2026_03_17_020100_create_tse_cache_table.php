<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tse_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->string('cpf', 11);
            $table->json('dados_json');
            $table->dateTime('consultado_em');
            $table->dateTime('expira_em');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
            $table->unique(['prefeitura_id', 'cpf']);
            $table->index(['cpf', 'expira_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tse_cache');
    }
};
