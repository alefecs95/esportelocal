<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campeonato_categorias', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_categorias', 'competitiva')) {
                $table->boolean('competitiva')->default(true)->after('vagas_limite');
            }
            if (!Schema::hasColumn('campeonato_categorias', 'contribuicao_alimento_kg')) {
                $table->decimal('contribuicao_alimento_kg', 8, 2)->nullable()->after('competitiva');
            }
            if (!Schema::hasColumn('campeonato_categorias', 'aceita_brinquedo')) {
                $table->boolean('aceita_brinquedo')->default(false)->after('contribuicao_alimento_kg');
            }
            if (!Schema::hasColumn('campeonato_categorias', 'premiacao_descricao')) {
                $table->string('premiacao_descricao')->nullable()->after('aceita_brinquedo');
            }
            if (!Schema::hasColumn('campeonato_categorias', 'largada_prevista')) {
                $table->string('largada_prevista', 20)->nullable()->after('premiacao_descricao');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campeonato_categorias', function (Blueprint $table) {
            $cols = array_filter([
                'competitiva',
                'contribuicao_alimento_kg',
                'aceita_brinquedo',
                'premiacao_descricao',
                'largada_prevista',
            ], fn ($col) => Schema::hasColumn('campeonato_categorias', $col));

            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
