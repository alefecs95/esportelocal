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
        Schema::table('partidas', function (Blueprint $table) {
            $table->text('assinatura_arbitro')->nullable();
            $table->text('assinatura_mandante')->nullable();
            $table->text('assinatura_visitante')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partidas', function (Blueprint $table) {
            $table->dropColumn(['assinatura_arbitro', 'assinatura_mandante', 'assinatura_visitante']);
        });
    }
};
