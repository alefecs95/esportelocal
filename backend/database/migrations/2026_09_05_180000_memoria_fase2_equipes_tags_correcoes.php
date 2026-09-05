<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memoria_acervo', function (Blueprint $table) {
            if (!Schema::hasColumn('memoria_acervo', 'aparece_na_timeline')) {
                $table->boolean('aparece_na_timeline')->default(false)->after('publicado_em');
            }
        });

        Schema::table('memoria_personalidades', function (Blueprint $table) {
            if (!Schema::hasColumn('memoria_personalidades', 'apelido')) {
                $table->string('apelido')->nullable()->after('nome');
            }
            if (!Schema::hasColumn('memoria_personalidades', 'atleta_id')) {
                $table->foreignId('atleta_id')->nullable()->after('modalidade_id')->constrained('atletas')->nullOnDelete();
            }
        });

        if (!Schema::hasTable('memoria_equipes')) {
            Schema::create('memoria_equipes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prefeitura_id');
                $table->string('nome');
                $table->string('slug');
                $table->string('nome_anterior')->nullable();
                $table->string('apelido')->nullable();
                $table->string('escudo')->nullable();
                $table->foreignId('modalidade_id')->nullable()->constrained('modalidades')->nullOnDelete();
                $table->unsignedSmallInteger('ano_inicio')->nullable();
                $table->unsignedSmallInteger('ano_fim')->nullable();
                $table->string('cidade')->nullable();
                $table->text('descricao')->nullable();
                $table->text('historia')->nullable();
                $table->boolean('publicado')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->index('prefeitura_id');
                $table->unique(['prefeitura_id', 'slug']);
                $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('memoria_equipe_personalidade')) {
            Schema::create('memoria_equipe_personalidade', function (Blueprint $table) {
                $table->id();
                $table->foreignId('equipe_id')->constrained('memoria_equipes')->cascadeOnDelete();
                $table->foreignId('personalidade_id')->constrained('memoria_personalidades')->cascadeOnDelete();
                $table->string('papel')->default('atleta');
                $table->unsignedSmallInteger('ano_inicio')->nullable();
                $table->unsignedSmallInteger('ano_fim')->nullable();
                $table->timestamps();
                $table->unique(['equipe_id', 'personalidade_id']);
            });
        }

        if (!Schema::hasTable('memoria_acervo_equipe')) {
            Schema::create('memoria_acervo_equipe', function (Blueprint $table) {
                $table->id();
                $table->foreignId('acervo_id')->constrained('memoria_acervo')->cascadeOnDelete();
                $table->foreignId('equipe_id')->constrained('memoria_equipes')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['acervo_id', 'equipe_id']);
            });
        }

        if (!Schema::hasTable('memoria_marco_equipe')) {
            Schema::create('memoria_marco_equipe', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marco_id')->constrained('memoria_marcos')->cascadeOnDelete();
                $table->foreignId('equipe_id')->constrained('memoria_equipes')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['marco_id', 'equipe_id']);
            });
        }

        if (!Schema::hasTable('memoria_acervo_marco')) {
            Schema::create('memoria_acervo_marco', function (Blueprint $table) {
                $table->id();
                $table->foreignId('acervo_id')->constrained('memoria_acervo')->cascadeOnDelete();
                $table->foreignId('marco_id')->constrained('memoria_marcos')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['acervo_id', 'marco_id']);
            });
        }

        if (!Schema::hasTable('memoria_tags')) {
            Schema::create('memoria_tags', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prefeitura_id');
                $table->string('nome');
                $table->string('slug');
                $table->timestamps();
                $table->index('prefeitura_id');
                $table->unique(['prefeitura_id', 'slug']);
                $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('memoria_acervo_tag')) {
            Schema::create('memoria_acervo_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('acervo_id')->constrained('memoria_acervo')->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained('memoria_tags')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['acervo_id', 'tag_id']);
            });
        }

        if (!Schema::hasTable('memoria_correcoes')) {
            Schema::create('memoria_correcoes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prefeitura_id');
                $table->foreignId('acervo_id')->constrained('memoria_acervo')->cascadeOnDelete();
                $table->string('tipo');
                $table->string('valor')->nullable();
                $table->text('comentario')->nullable();
                $table->string('doador_nome')->nullable();
                $table->string('doador_telefone')->nullable();
                $table->unsignedBigInteger('local_id')->nullable();
                $table->unsignedBigInteger('equipe_id')->nullable();
                $table->unsignedBigInteger('marco_id')->nullable();
                $table->unsignedSmallInteger('ano_sugerido')->nullable();
                $table->string('fingerprint')->nullable();
                $table->string('status')->default('pendente');
                $table->text('motivo_rejeicao')->nullable();
                $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('revisado_em')->nullable();
                $table->timestamps();
                $table->index(['prefeitura_id', 'status']);
                $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
            });
        }

        DB::table('memoria_acervo')->whereNotNull('marco_id')->where('publicado', true)->update(['aparece_na_timeline' => true]);

        $comMarco = DB::table('memoria_acervo')->whereNotNull('marco_id')->get(['id', 'marco_id']);
        foreach ($comMarco as $row) {
            $exists = DB::table('memoria_acervo_marco')->where('acervo_id', $row->id)->where('marco_id', $row->marco_id)->exists();
            if (!$exists) {
                DB::table('memoria_acervo_marco')->insert([
                    'acervo_id' => $row->id,
                    'marco_id' => $row->marco_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('memoria_correcoes');
        Schema::dropIfExists('memoria_acervo_tag');
        Schema::dropIfExists('memoria_tags');
        Schema::dropIfExists('memoria_acervo_marco');
        Schema::dropIfExists('memoria_marco_equipe');
        Schema::dropIfExists('memoria_acervo_equipe');
        Schema::dropIfExists('memoria_equipe_personalidade');
        Schema::dropIfExists('memoria_equipes');

        Schema::table('memoria_personalidades', function (Blueprint $table) {
            if (Schema::hasColumn('memoria_personalidades', 'atleta_id')) {
                $table->dropConstrainedForeignId('atleta_id');
            }
            if (Schema::hasColumn('memoria_personalidades', 'apelido')) {
                $table->dropColumn('apelido');
            }
        });
        Schema::table('memoria_acervo', function (Blueprint $table) {
            if (Schema::hasColumn('memoria_acervo', 'aparece_na_timeline')) {
                $table->dropColumn('aparece_na_timeline');
            }
        });
    }
};
