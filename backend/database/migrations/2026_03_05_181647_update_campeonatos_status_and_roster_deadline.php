<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE campeonatos MODIFY COLUMN status ENUM('planejado','aberto','cancelado','draft','inscricoes_abertas','inscricoes_encerradas','em_andamento','finalizado') NOT NULL DEFAULT 'planejado'");

        DB::statement("UPDATE campeonatos SET status = 'draft' WHERE status = 'planejado'");
        DB::statement("UPDATE campeonatos SET status = 'inscricoes_abertas' WHERE status = 'aberto'");
        DB::statement("UPDATE campeonatos SET status = 'inscricoes_encerradas' WHERE status = 'cancelado'");

        DB::statement("ALTER TABLE campeonatos MODIFY COLUMN status ENUM('draft','inscricoes_abertas','inscricoes_encerradas','em_andamento','finalizado') NOT NULL DEFAULT 'draft'");

        Schema::table('campeonatos', function (Blueprint $table) {
            $table->unsignedInteger('rodada_limite_inscricao_atletas')->nullable()->after('fim_inscricao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campeonatos', function (Blueprint $table) {
            $table->dropColumn('rodada_limite_inscricao_atletas');
        });

        DB::statement("ALTER TABLE campeonatos MODIFY COLUMN status ENUM('planejado','aberto','cancelado','draft','inscricoes_abertas','inscricoes_encerradas','em_andamento','finalizado') NOT NULL DEFAULT 'draft'");

        DB::statement("UPDATE campeonatos SET status = 'planejado' WHERE status = 'draft'");
        DB::statement("UPDATE campeonatos SET status = 'aberto' WHERE status = 'inscricoes_abertas'");
        DB::statement("UPDATE campeonatos SET status = 'cancelado' WHERE status = 'inscricoes_encerradas'");

        DB::statement("ALTER TABLE campeonatos MODIFY COLUMN status ENUM('planejado','aberto','em_andamento','finalizado','cancelado') NOT NULL DEFAULT 'planejado'");
    }
};
