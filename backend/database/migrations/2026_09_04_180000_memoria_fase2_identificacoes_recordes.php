<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('memoria_acervo', 'precisa_identificar')) {
            Schema::table('memoria_acervo', function (Blueprint $table) {
                $table->boolean('precisa_identificar')->default(false)->after('publicado');
            });
        }

        if (!Schema::hasTable('memoria_identificacoes')) {
            Schema::create('memoria_identificacoes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prefeitura_id');
                $table->foreignId('acervo_id')->constrained('memoria_acervo')->cascadeOnDelete();
                $table->foreignId('personalidade_id')->nullable()->constrained('memoria_personalidades')->nullOnDelete();
                $table->string('nome_sugerido');
                $table->text('comentario')->nullable();
                $table->string('doador_nome')->nullable();
                $table->string('doador_telefone', 30)->nullable();
                $table->string('fingerprint', 64);
                $table->string('status')->default('pendente');
                $table->text('motivo_rejeicao')->nullable();
                $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('revisado_em')->nullable();
                $table->timestamps();

                $table->index(['prefeitura_id', 'status']);
                $table->unique(['prefeitura_id', 'acervo_id', 'fingerprint'], 'memoria_identificacoes_unique');
                $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('memoria_recordes')) {
            Schema::create('memoria_recordes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prefeitura_id');
                $table->string('titulo');
                $table->string('categoria')->default('outro');
                $table->string('valor')->nullable();
                $table->string('unidade')->nullable();
                $table->unsignedSmallInteger('ano')->nullable();
                $table->foreignId('modalidade_id')->nullable()->constrained('modalidades')->nullOnDelete();
                $table->foreignId('campeonato_id')->nullable()->constrained('campeonatos')->nullOnDelete();
                $table->foreignId('personalidade_id')->nullable()->constrained('memoria_personalidades')->nullOnDelete();
                $table->foreignId('marco_id')->nullable()->constrained('memoria_marcos')->nullOnDelete();
                $table->string('atleta_nome')->nullable();
                $table->text('descricao')->nullable();
                $table->boolean('vigente')->default(true);
                $table->boolean('publicado')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['prefeitura_id', 'publicado']);
                $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('memoria_recordes');
        Schema::dropIfExists('memoria_identificacoes');
        Schema::table('memoria_acervo', function (Blueprint $table) {
            if (Schema::hasColumn('memoria_acervo', 'precisa_identificar')) {
                $table->dropColumn('precisa_identificar');
            }
        });
    }
};
