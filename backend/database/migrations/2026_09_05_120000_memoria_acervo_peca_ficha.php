<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memoria_acervo', function (Blueprint $table) {
            if (!Schema::hasColumn('memoria_acervo', 'slug')) {
                $table->string('slug')->nullable()->after('titulo');
            }
            if (!Schema::hasColumn('memoria_acervo', 'credito')) {
                $table->string('credito')->nullable()->after('doacao');
            }
            if (!Schema::hasColumn('memoria_acervo', 'fotografo')) {
                $table->string('fotografo')->nullable()->after('credito');
            }
            if (!Schema::hasColumn('memoria_acervo', 'licenca')) {
                $table->string('licenca')->nullable()->after('fotografo');
            }
            if (!Schema::hasColumn('memoria_acervo', 'data_incerta')) {
                $table->boolean('data_incerta')->default(false)->after('decada');
            }
            if (!Schema::hasColumn('memoria_acervo', 'publicado_em')) {
                $table->timestamp('publicado_em')->nullable()->after('publicado');
            }
        });

        try {
            Schema::table('memoria_acervo', function (Blueprint $table) {
                $table->unique(['prefeitura_id', 'slug']);
            });
        } catch (\Throwable $e) {
            // índice já existe em reexecução parcial
        }

        if (!Schema::hasTable('memoria_acervo_personalidade')) {
            Schema::create('memoria_acervo_personalidade', function (Blueprint $table) {
                $table->id();
                $table->foreignId('acervo_id')->constrained('memoria_acervo')->cascadeOnDelete();
                $table->foreignId('personalidade_id')->constrained('memoria_personalidades')->cascadeOnDelete();
                $table->string('papel')->default('outro');
                $table->unsignedInteger('ordem')->default(0);
                $table->string('observacao')->nullable();
                $table->timestamps();
                $table->unique(['acervo_id', 'personalidade_id']);
            });
        }

        $this->backfill();
    }

    private function backfill(): void
    {
        $rows = DB::table('memoria_acervo')->orderBy('id')->get();
        $used = [];

        foreach ($rows as $row) {
            $updates = [];

            if (empty($row->slug)) {
                $base = Str::slug($row->titulo ?: 'peca') ?: 'peca';
                $slug = $base;
                $i = 1;
                $key = $row->prefeitura_id . ':' . $slug;
                while (isset($used[$key]) || DB::table('memoria_acervo')->where('prefeitura_id', $row->prefeitura_id)->where('slug', $slug)->where('id', '!=', $row->id)->exists()) {
                    $slug = $base . '-' . (++$i);
                    $key = $row->prefeitura_id . ':' . $slug;
                }
                $used[$key] = true;
                $updates['slug'] = $slug;
            } else {
                $used[$row->prefeitura_id . ':' . $row->slug] = true;
            }

            if (empty($row->credito) && !empty($row->fonte)) {
                $updates['credito'] = $row->fonte;
            }

            if (empty($row->licenca)) {
                $updates['licenca'] = 'nao_informado';
            }

            if (empty($row->publicado_em) && $row->status === 'aprovado' && $row->publicado) {
                $updates['publicado_em'] = $row->revisado_em ?: $row->updated_at ?: $row->created_at;
            }

            if ($updates) {
                DB::table('memoria_acervo')->where('id', $row->id)->update($updates);
            }

            if ($row->personalidade_id) {
                $exists = DB::table('memoria_acervo_personalidade')
                    ->where('acervo_id', $row->id)
                    ->where('personalidade_id', $row->personalidade_id)
                    ->exists();
                if (!$exists) {
                    DB::table('memoria_acervo_personalidade')->insert([
                        'acervo_id' => $row->id,
                        'personalidade_id' => $row->personalidade_id,
                        'papel' => 'outro',
                        'ordem' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('memoria_acervo_personalidade');

        Schema::table('memoria_acervo', function (Blueprint $table) {
            $table->dropUnique(['prefeitura_id', 'slug']);
            foreach (['slug', 'credito', 'fotografo', 'licenca', 'data_incerta', 'publicado_em'] as $column) {
                if (Schema::hasColumn('memoria_acervo', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
