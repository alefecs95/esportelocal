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
            if (!Schema::hasColumn('memoria_acervo', 'transcricao')) {
                $table->text('transcricao')->nullable()->after('historia');
            }
            if (!Schema::hasColumn('memoria_acervo', 'hash_arquivo')) {
                $table->string('hash_arquivo', 64)->nullable()->after('tamanho');
                $table->index('hash_arquivo');
            }
            if (!Schema::hasColumn('memoria_acervo', 'visualizacoes')) {
                $table->unsignedInteger('visualizacoes')->default(0)->after('aparece_na_timeline');
            }
        });

        foreach (['memoria_marcos', 'memoria_personalidades', 'memoria_locais', 'memoria_equipes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'visualizacoes')) {
                    $table->unsignedInteger('visualizacoes')->default(0);
                }
            });
        }

        if (!Schema::hasTable('memoria_local_momentos')) {
            Schema::create('memoria_local_momentos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prefeitura_id');
                $table->foreignId('local_id')->constrained('memoria_locais')->cascadeOnDelete();
                $table->unsignedSmallInteger('ano')->nullable();
                $table->string('titulo')->nullable();
                $table->text('descricao')->nullable();
                $table->string('foto')->nullable();
                $table->foreignId('acervo_id')->nullable()->constrained('memoria_acervo')->nullOnDelete();
                $table->unsignedSmallInteger('ordem')->default(0);
                $table->timestamps();
                $table->index('prefeitura_id');
                $table->index(['local_id', 'ano']);
                $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
            });
        }

        $locais = DB::table('memoria_locais')->whereNull('deleted_at')->get();
        foreach ($locais as $local) {
            $exists = DB::table('memoria_local_momentos')->where('local_id', $local->id)->exists();
            if ($exists) {
                continue;
            }
            $ordem = 0;
            if (!empty($local->foto_antes)) {
                DB::table('memoria_local_momentos')->insert([
                    'prefeitura_id' => $local->prefeitura_id,
                    'local_id' => $local->id,
                    'ano' => $local->ano_antes,
                    'titulo' => 'Antes',
                    'foto' => $local->foto_antes,
                    'ordem' => $ordem++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if (!empty($local->foto_depois)) {
                DB::table('memoria_local_momentos')->insert([
                    'prefeitura_id' => $local->prefeitura_id,
                    'local_id' => $local->id,
                    'ano' => $local->ano_depois,
                    'titulo' => 'Depois',
                    'foto' => $local->foto_depois,
                    'ordem' => $ordem++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('memoria_local_momentos');

        Schema::table('memoria_acervo', function (Blueprint $table) {
            if (Schema::hasColumn('memoria_acervo', 'transcricao')) {
                $table->dropColumn('transcricao');
            }
            if (Schema::hasColumn('memoria_acervo', 'hash_arquivo')) {
                $table->dropIndex(['hash_arquivo']);
                $table->dropColumn('hash_arquivo');
            }
            if (Schema::hasColumn('memoria_acervo', 'visualizacoes')) {
                $table->dropColumn('visualizacoes');
            }
        });

        foreach (['memoria_marcos', 'memoria_personalidades', 'memoria_locais', 'memoria_equipes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'visualizacoes')) {
                    $table->dropColumn('visualizacoes');
                }
            });
        }
    }
};
