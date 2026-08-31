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
        Schema::table('campeonatos', function (Blueprint $table) {
            $table->boolean('permite_inscricao_online')->default(false)->after('status');
        });

        Schema::table('equipes', function (Blueprint $table) {
            $table->enum('status_inscricao', ['pendente', 'aprovada', 'rejeitada'])->default('aprovada')->after('contato');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campeonatos', function (Blueprint $table) {
            $table->dropColumn('permite_inscricao_online');
        });

        Schema::table('equipes', function (Blueprint $table) {
            $table->dropColumn('status_inscricao');
        });
    }
};
