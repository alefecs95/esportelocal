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
            $table->integer('idade_min')->nullable()->after('regulamento');
            $table->integer('idade_max')->nullable()->after('idade_min');
            $table->string('cidade_eleitoral')->nullable()->after('idade_max');
            $table->string('bairro_eleitoral')->nullable()->after('cidade_eleitoral');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campeonatos', function (Blueprint $table) {
            $table->dropColumn(['idade_min', 'idade_max', 'cidade_eleitoral', 'bairro_eleitoral']);
        });
    }
};
