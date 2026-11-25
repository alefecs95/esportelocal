<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campeonatos', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonatos', 'modo_cadastro')) {
                $table->string('modo_cadastro', 30)->default('completo')->after('permite_inscricao_online');
            }
            if (!Schema::hasColumn('campeonatos', 'campos_cadastro')) {
                $table->json('campos_cadastro')->nullable()->after('modo_cadastro');
            }
            if (!Schema::hasColumn('campeonatos', 'inscricao_solidaria')) {
                $table->boolean('inscricao_solidaria')->default(false)->after('campos_cadastro');
            }
            if (!Schema::hasColumn('campeonatos', 'exige_termo_menor')) {
                $table->boolean('exige_termo_menor')->default(false)->after('inscricao_solidaria');
            }
            if (!Schema::hasColumn('campeonatos', 'kit_retirada_info')) {
                $table->text('kit_retirada_info')->nullable()->after('exige_termo_menor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campeonatos', function (Blueprint $table) {
            $cols = array_filter([
                'modo_cadastro',
                'campos_cadastro',
                'inscricao_solidaria',
                'exige_termo_menor',
                'kit_retirada_info',
            ], fn ($col) => Schema::hasColumn('campeonatos', $col));

            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
