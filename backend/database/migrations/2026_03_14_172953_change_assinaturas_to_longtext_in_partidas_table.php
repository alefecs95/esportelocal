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
            $table->longText('assinatura_arbitro')->nullable()->change();
            $table->longText('assinatura_mandante')->nullable()->change();
            $table->longText('assinatura_visitante')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partidas', function (Blueprint $table) {
            $table->text('assinatura_arbitro')->nullable()->change();
            $table->text('assinatura_mandante')->nullable()->change();
            $table->text('assinatura_visitante')->nullable()->change();
        });
    }
};
