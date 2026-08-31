<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partidas', function (Blueprint $table) {
            $table->foreignId('fase_grupo_id')->nullable()->after('fase_id')->constrained('fase_grupos')->nullOnDelete();
            $table->unsignedInteger('ordem_na_fase')->nullable()->after('chave');
            $table->unsignedBigInteger('vencedor_id')->nullable()->after('proxima_partida_id');
            $table->string('origem_mandante_type', 50)->nullable()->after('vencedor_id');
            $table->json('origem_mandante_payload')->nullable()->after('origem_mandante_type');
            $table->string('origem_visitante_type', 50)->nullable()->after('origem_mandante_payload');
            $table->json('origem_visitante_payload')->nullable()->after('origem_visitante_type');
            $table->boolean('definido_manualmente')->default(false)->after('origem_visitante_payload');
        });
    }

    public function down(): void
    {
        Schema::table('partidas', function (Blueprint $table) {
            $table->dropForeign(['fase_grupo_id']);
            $table->dropColumn([
                'fase_grupo_id',
                'ordem_na_fase',
                'vencedor_id',
                'origem_mandante_type',
                'origem_mandante_payload',
                'origem_visitante_type',
                'origem_visitante_payload',
                'definido_manualmente',
            ]);
        });
    }
};
