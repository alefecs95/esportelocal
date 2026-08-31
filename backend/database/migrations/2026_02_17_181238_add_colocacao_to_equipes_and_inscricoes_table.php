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
        Schema::table('equipes', function (Blueprint $table) {
            $table->integer('colocacao')->nullable()->after('contato');
        });

        Schema::table('inscricoes', function (Blueprint $table) {
            $table->integer('colocacao')->nullable()->after('data_pagamento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipes', function (Blueprint $table) {
            $table->dropColumn('colocacao');
        });

        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropColumn('colocacao');
        });
    }
};
