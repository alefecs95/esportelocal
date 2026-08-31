<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulta_historicos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->string('tipo', 20);
            $table->string('status', 20);
            $table->string('origem', 20)->nullable();
            $table->string('cpf_consultado', 14)->nullable();
            $table->string('cpf_hash', 64)->nullable();
            $table->text('mensagem')->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('consultado_em');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
            $table->index(['prefeitura_id', 'tipo', 'consultado_em']);
            $table->index(['prefeitura_id', 'status', 'consultado_em']);
            $table->index('cpf_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulta_historicos');
    }
};
