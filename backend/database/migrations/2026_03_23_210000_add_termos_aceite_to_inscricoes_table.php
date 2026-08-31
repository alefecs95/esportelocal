<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->timestamp('aceitou_termos_imagem_em')->nullable()->after('data_inscricao');
            $table->timestamp('aceitou_regulamento_em')->nullable()->after('aceitou_termos_imagem_em');
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropColumn(['aceitou_termos_imagem_em', 'aceitou_regulamento_em']);
        });
    }
};
