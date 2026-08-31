<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prefeituras', function (Blueprint $table) {
            $table->json('configuracoes_integracoes')->nullable()->after('configuracao_estilo');
        });
    }

    public function down(): void
    {
        Schema::table('prefeituras', function (Blueprint $table) {
            $table->dropColumn('configuracoes_integracoes');
        });
    }
};
