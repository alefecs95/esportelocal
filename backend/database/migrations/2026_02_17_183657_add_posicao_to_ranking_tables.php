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
        Schema::table('ranking_atletas', function (Blueprint $table) {
            $table->integer('posicao')->nullable()->after('pontos');
        });

        Schema::table('ranking_equipes', function (Blueprint $table) {
            $table->integer('posicao')->nullable()->after('pontos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ranking_atletas', function (Blueprint $table) {
            $table->dropColumn('posicao');
        });

        Schema::table('ranking_equipes', function (Blueprint $table) {
            $table->dropColumn('posicao');
        });
    }
};
