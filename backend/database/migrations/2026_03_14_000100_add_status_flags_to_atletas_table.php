<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atletas', function (Blueprint $table) {
            $table->boolean('acesso_ativo')->default(true)->after('foto');
            $table->boolean('pode_participar_competicoes')->default(true)->after('acesso_ativo');
        });
    }

    public function down(): void
    {
        Schema::table('atletas', function (Blueprint $table) {
            $table->dropColumn(['acesso_ativo', 'pode_participar_competicoes']);
        });
    }
};
