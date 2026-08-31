<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fases', function (Blueprint $table) {
            $table->enum('tipo', ['grupos', 'mata_mata', 'pontos_corridos', 'custom'])->default('custom')->after('nome');
            $table->enum('status', ['planejada', 'ativa', 'encerrada'])->default('planejada')->after('ordem');
            $table->json('configuracao')->nullable()->after('status');
            $table->boolean('permite_edicao_manual')->default(true)->after('configuracao');
        });
    }

    public function down(): void
    {
        Schema::table('fases', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'status', 'configuracao', 'permite_edicao_manual']);
        });
    }
};
