<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            if (!Schema::hasColumn('inscricoes', 'numero_peito')) {
                $table->unsignedInteger('numero_peito')->nullable()->after('campeonato_categoria_id');
                $table->unique(['campeonato_id', 'numero_peito'], 'inscricoes_campeonato_numero_peito_unique');
            }
            if (!Schema::hasColumn('inscricoes', 'forma_contribuicao')) {
                $table->string('forma_contribuicao', 30)->nullable()->after('status_pagamento');
            }
            if (!Schema::hasColumn('inscricoes', 'contribuicao_quantidade')) {
                $table->decimal('contribuicao_quantidade', 8, 2)->nullable()->after('forma_contribuicao');
            }
            if (!Schema::hasColumn('inscricoes', 'contribuicao_recebida_em')) {
                $table->timestamp('contribuicao_recebida_em')->nullable()->after('contribuicao_quantidade');
            }
            if (!Schema::hasColumn('inscricoes', 'kit_retirado_em')) {
                $table->timestamp('kit_retirado_em')->nullable()->after('contribuicao_recebida_em');
            }
            if (!Schema::hasColumn('inscricoes', 'kit_retirado_por_nome')) {
                $table->string('kit_retirado_por_nome')->nullable()->after('kit_retirado_em');
            }
            if (!Schema::hasColumn('inscricoes', 'kit_retirado_por_documento')) {
                $table->string('kit_retirado_por_documento', 50)->nullable()->after('kit_retirado_por_nome');
            }
            if (!Schema::hasColumn('inscricoes', 'termo_responsavel_assinado_em')) {
                $table->timestamp('termo_responsavel_assinado_em')->nullable()->after('kit_retirado_por_documento');
            }
            if (!Schema::hasColumn('inscricoes', 'responsavel_nome')) {
                $table->string('responsavel_nome')->nullable()->after('termo_responsavel_assinado_em');
            }
            if (!Schema::hasColumn('inscricoes', 'colocacao_categoria')) {
                $table->unsignedInteger('colocacao_categoria')->nullable()->after('colocacao');
            }
            if (!Schema::hasColumn('inscricoes', 'finalizou_em')) {
                $table->timestamp('finalizou_em')->nullable()->after('tempo_ms');
            }
            if (!Schema::hasColumn('inscricoes', 'premiado_em')) {
                $table->timestamp('premiado_em')->nullable()->after('finalizou_em');
            }
            if (!Schema::hasColumn('inscricoes', 'premiacao_descricao')) {
                $table->string('premiacao_descricao')->nullable()->after('premiado_em');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            if (Schema::hasColumn('inscricoes', 'numero_peito')) {
                $table->dropUnique('inscricoes_campeonato_numero_peito_unique');
            }

            $cols = array_filter([
                'numero_peito',
                'forma_contribuicao',
                'contribuicao_quantidade',
                'contribuicao_recebida_em',
                'kit_retirado_em',
                'kit_retirado_por_nome',
                'kit_retirado_por_documento',
                'termo_responsavel_assinado_em',
                'responsavel_nome',
                'colocacao_categoria',
                'finalizou_em',
                'premiado_em',
                'premiacao_descricao',
            ], fn ($col) => Schema::hasColumn('inscricoes', $col));

            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
