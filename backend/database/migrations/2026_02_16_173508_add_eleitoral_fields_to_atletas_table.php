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
        Schema::table('atletas', function (Blueprint $table) {
            $table->string('local_votacao')->nullable()->after('secao_eleitoral');
            $table->string('endereco_eleitoral')->nullable()->after('local_votacao');
            $table->string('numero_eleitoral')->nullable()->after('endereco_eleitoral');
            $table->string('bairro_eleitoral')->nullable()->after('numero_eleitoral');
            $table->string('cidade_eleitoral')->nullable()->after('bairro_eleitoral');
            $table->string('uf_eleitoral')->nullable()->after('cidade_eleitoral');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atletas', function (Blueprint $table) {
            $table->dropColumn([
                'local_votacao',
                'endereco_eleitoral',
                'numero_eleitoral',
                'bairro_eleitoral',
                'cidade_eleitoral',
                'uf_eleitoral'
            ]);
        });
    }
};
