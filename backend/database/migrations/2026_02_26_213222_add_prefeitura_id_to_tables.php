<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Criar um tenant padrão para migrar dados existentes
        $defaultTenantId = DB::table('prefeituras')->insertGetId([
            'nome' => 'Prefeitura Padrão',
            'slug' => 'padrao',
            'dominio' => null,
            'cor_primaria' => '#000000',
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tables = [
            'users',
            'atletas',
            'modalidades',
            'campeonatos',
            'equipes',
            'inscricoes',
            'partidas',
            'temporadas',
            'entidades',
            'ranking_atletas',
            'ranking_equipes'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($defaultTenantId, $tableName) {
                    $table->unsignedBigInteger('prefeitura_id')->nullable()->after('id');
                    
                    // Index para performance em queries tenant-scoped
                    $table->index('prefeitura_id');
                });

                // Migrar dados existentes para o tenant padrão
                DB::table($tableName)->update(['prefeitura_id' => $defaultTenantId]);

                // Tornar obrigatório e adicionar FK
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('prefeitura_id')->nullable(false)->change();
                    $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'ranking_equipes',
            'ranking_atletas',
            'entidades',
            'temporadas',
            'partidas',
            'inscricoes',
            'equipes',
            'campeonatos',
            'modalidades',
            'atletas',
            'users'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['prefeitura_id']);
                    $table->dropColumn('prefeitura_id');
                });
            }
        }
    }
};
