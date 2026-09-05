<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addPrefeituraId('fases', '
            UPDATE fases
            INNER JOIN campeonatos ON fases.campeonato_id = campeonatos.id
            SET fases.prefeitura_id = campeonatos.prefeitura_id
            WHERE fases.prefeitura_id IS NULL
        ', 'campeonatos');

        $this->addPrefeituraId('eventos_partida', '
            UPDATE eventos_partida
            INNER JOIN partidas ON eventos_partida.partida_id = partidas.id
            SET eventos_partida.prefeitura_id = partidas.prefeitura_id
            WHERE eventos_partida.prefeitura_id IS NULL
        ', 'partidas');

        $this->addPrefeituraId('comentarios', '
            UPDATE comentarios
            INNER JOIN noticias ON comentarios.noticia_id = noticias.id
            SET comentarios.prefeitura_id = noticias.prefeitura_id
            WHERE comentarios.prefeitura_id IS NULL
        ', 'noticias');

        $this->addPrefeituraId('atleta_arquivos', '
            UPDATE atleta_arquivos
            INNER JOIN atletas ON atleta_arquivos.atleta_id = atletas.id
            SET atleta_arquivos.prefeitura_id = atletas.prefeitura_id
            WHERE atleta_arquivos.prefeitura_id IS NULL
        ', 'atletas');
    }

    public function down(): void
    {
        foreach (['atleta_arquivos', 'comentarios', 'eventos_partida', 'fases'] as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'prefeitura_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['prefeitura_id']);
                $table->dropColumn('prefeitura_id');
            });
        }
    }

    private function addPrefeituraId(string $tableName, string $backfillSql, string $parentTable): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'prefeitura_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->unsignedBigInteger('prefeitura_id')->nullable()->after('id');
            $table->index('prefeitura_id');
        });

        if (Schema::hasTable($parentTable)) {
            DB::statement($backfillSql);
        }

        $fallbackId = DB::table('prefeituras')->orderBy('id')->value('id');
        if ($fallbackId) {
            DB::table($tableName)->whereNull('prefeitura_id')->update(['prefeitura_id' => $fallbackId]);
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
        });
    }
};
