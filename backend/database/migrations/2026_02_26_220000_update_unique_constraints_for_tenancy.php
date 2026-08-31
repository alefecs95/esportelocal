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
        // Users: email unique -> (email, prefeitura_id) unique
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['email', 'prefeitura_id']);
        });

        // Atletas: cpf unique -> (cpf, prefeitura_id) unique
        Schema::table('atletas', function (Blueprint $table) {
            $table->dropUnique(['cpf']);
            $table->unique(['cpf', 'prefeitura_id']);
        });
        
        // Modalidades: nome unique (se houver) -> (nome, prefeitura_id)
        // Verificando se existe unique em modalidades... Geralmente sim para evitar duplicação
        // Se não existir, mal não faz adicionar unique composto se desejado.
        // Mas vamos focar nos críticos.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email', 'prefeitura_id']);
            $table->unique('email'); // Isso pode falhar se houver duplicatas criadas
        });

        Schema::table('atletas', function (Blueprint $table) {
            $table->dropUnique(['cpf', 'prefeitura_id']);
            $table->unique('cpf');
        });
    }
};
