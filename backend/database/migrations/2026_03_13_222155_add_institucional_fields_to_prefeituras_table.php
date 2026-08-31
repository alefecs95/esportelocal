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
            $table->string('endereco')->nullable()->after('dominio');
            $table->string('telefone')->nullable()->after('endereco');
            $table->string('email_contato')->nullable()->after('telefone');
            $table->json('redes_sociais')->nullable()->after('email_contato');
            // cor_secundaria já existe na tabela original, vou adicionar cor_terciaria e cor_texto_menu se necessário para personalização avançada, mas o user pediu só "cores da plataforma".
            // Vou manter simples por enquanto.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prefeituras', function (Blueprint $table) {
            $table->dropColumn(['endereco', 'telefone', 'email_contato', 'redes_sociais']);
        });
    }
};
