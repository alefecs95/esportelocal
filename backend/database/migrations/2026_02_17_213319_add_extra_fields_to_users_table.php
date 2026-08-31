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
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf')->unique()->nullable()->after('name');
            $table->string('perfil')->default('ATLETA')->after('cpf');
            $table->string('status')->default('ATIVO')->after('perfil');
            $table->boolean('forcar_troca_senha')->default(true)->after('status');
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'perfil', 'status', 'forcar_troca_senha']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
