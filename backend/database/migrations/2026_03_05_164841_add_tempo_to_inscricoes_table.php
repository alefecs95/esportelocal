<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            // Tempo visual (ex: "01:25:30")
            $table->string('tempo_visual')->nullable()->after('colocacao');
            // Tempo em milissegundos para ordenação (ex: 5130000)
            $table->bigInteger('tempo_ms')->nullable()->after('tempo_visual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropColumn(['tempo_visual', 'tempo_ms']);
        });
    }
};
