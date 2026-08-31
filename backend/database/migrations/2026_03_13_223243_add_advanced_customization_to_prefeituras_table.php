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
        Schema::table('prefeituras', function (Blueprint $table) {
            $table->string('banner_portal')->nullable()->after('logo');
            $table->string('fundo_login')->nullable()->after('banner_portal');
            $table->json('configuracao_estilo')->nullable()->after('cor_secundaria');
            // 'configuracao_estilo' será um JSON para guardar outras cores e preferências visuais
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prefeituras', function (Blueprint $table) {
            $table->dropColumn(['banner_portal', 'fundo_login', 'configuracao_estilo']);
        });
    }
};
