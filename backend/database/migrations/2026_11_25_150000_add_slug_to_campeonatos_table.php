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
        if (!Schema::hasColumn('campeonatos', 'slug')) {
            Schema::table('campeonatos', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('nome');
            });
        }

        $rows = DB::table('campeonatos')
            ->select('id', 'prefeitura_id', 'nome', 'slug')
            ->orderBy('id')
            ->get();

        $used = [];
        foreach ($rows as $row) {
            if (!empty($row->slug)) {
                $used[$row->prefeitura_id . ':' . $row->slug] = true;
                continue;
            }

            $base = Str::slug((string) ($row->nome ?: 'competicao')) ?: 'competicao';
            $slug = $base;
            $i = 1;
            $key = $row->prefeitura_id . ':' . $slug;
            while (
                isset($used[$key]) ||
                DB::table('campeonatos')
                    ->where('prefeitura_id', $row->prefeitura_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $row->id)
                    ->exists()
            ) {
                $slug = $base . '-' . (++$i);
                $key = $row->prefeitura_id . ':' . $slug;
            }

            DB::table('campeonatos')
                ->where('id', $row->id)
                ->update(['slug' => $slug]);

            $used[$key] = true;
        }

        try {
            Schema::table('campeonatos', function (Blueprint $table) {
                $table->unique(['prefeitura_id', 'slug'], 'campeonatos_prefeitura_slug_unique');
            });
        } catch (\Throwable $e) {
            // Índice já existente
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('campeonatos', 'slug')) {
            try {
                Schema::table('campeonatos', function (Blueprint $table) {
                    $table->dropUnique('campeonatos_prefeitura_slug_unique');
                });
            } catch (\Throwable $e) {
                // Índice já removido
            }

            Schema::table('campeonatos', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
