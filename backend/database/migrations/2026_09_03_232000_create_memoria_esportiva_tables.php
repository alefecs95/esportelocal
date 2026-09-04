<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memoria_locais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->string('nome');
            $table->string('tipo')->default('outro'); // ginasio, estadio, quadra, pista, outro
            $table->string('endereco')->nullable();
            $table->text('descricao')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('publicado')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('prefeitura_id');
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
        });

        Schema::create('memoria_personalidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->string('nome');
            $table->string('slug');
            $table->string('papel')->default('atleta'); // atleta, tecnico, dirigente, outro
            $table->text('mini_bio')->nullable();
            $table->string('foto')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->date('data_falecimento')->nullable();
            $table->foreignId('modalidade_id')->nullable()->constrained('modalidades')->nullOnDelete();
            $table->boolean('destaque')->default(false);
            $table->boolean('publicado')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('prefeitura_id');
            $table->unique(['prefeitura_id', 'slug']);
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
        });

        Schema::create('memoria_marcos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->string('titulo');
            $table->string('slug');
            $table->string('resumo', 500)->nullable();
            $table->longText('conteudo')->nullable();
            $table->date('data_evento')->nullable();
            $table->unsignedSmallInteger('ano')->nullable();
            $table->unsignedSmallInteger('decada')->nullable();
            $table->string('tipo')->default('marco'); // conquista, campeonato, inauguracao, atleta, marco
            $table->foreignId('modalidade_id')->nullable()->constrained('modalidades')->nullOnDelete();
            $table->foreignId('local_id')->nullable()->constrained('memoria_locais')->nullOnDelete();
            $table->string('capa')->nullable();
            $table->boolean('destaque')->default(false);
            $table->boolean('publicado')->default(true);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('prefeitura_id');
            $table->index(['prefeitura_id', 'decada']);
            $table->unique(['prefeitura_id', 'slug']);
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
        });

        Schema::create('memoria_marco_personalidade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marco_id')->constrained('memoria_marcos')->cascadeOnDelete();
            $table->foreignId('personalidade_id')->constrained('memoria_personalidades')->cascadeOnDelete();
            $table->unique(['marco_id', 'personalidade_id']);
        });

        Schema::create('memoria_acervo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->string('tipo')->default('foto'); // foto, video, documento
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('arquivo');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('tamanho')->nullable();
            $table->date('data_aproximada')->nullable();
            $table->unsignedSmallInteger('ano')->nullable();
            $table->unsignedSmallInteger('decada')->nullable();
            $table->foreignId('local_id')->nullable()->constrained('memoria_locais')->nullOnDelete();
            $table->foreignId('modalidade_id')->nullable()->constrained('modalidades')->nullOnDelete();
            $table->foreignId('marco_id')->nullable()->constrained('memoria_marcos')->nullOnDelete();
            $table->foreignId('personalidade_id')->nullable()->constrained('memoria_personalidades')->nullOnDelete();
            $table->string('pessoas_envolvidas')->nullable();
            $table->string('fonte')->nullable();
            $table->string('origem')->default('curadoria'); // curadoria, comunidade
            $table->string('doador_nome')->nullable();
            $table->string('doador_email')->nullable();
            $table->string('doador_telefone')->nullable();
            $table->string('status')->default('aprovado'); // pendente, aprovado, rejeitado
            $table->text('motivo_rejeicao')->nullable();
            $table->boolean('publicado')->default(true);
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_em')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('prefeitura_id');
            $table->index(['prefeitura_id', 'status']);
            $table->index(['prefeitura_id', 'decada']);
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memoria_acervo');
        Schema::dropIfExists('memoria_marco_personalidade');
        Schema::dropIfExists('memoria_marcos');
        Schema::dropIfExists('memoria_personalidades');
        Schema::dropIfExists('memoria_locais');
    }
};
