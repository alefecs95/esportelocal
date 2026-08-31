<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cpf_cache')) {
            return;
        }

        DB::statement('DELETE c1 FROM cpf_cache c1 INNER JOIN cpf_cache c2 ON c1.cpf = c2.cpf AND c1.id < c2.id');

        Schema::table('cpf_cache', function (Blueprint $table) {
            try {
                $table->dropForeign(['prefeitura_id']);
            } catch (\Throwable $e) {
            }

            try {
                $table->dropUnique(['prefeitura_id', 'cpf']);
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex(['cpf']);
            } catch (\Throwable $e) {
            }
        });

        DB::statement('ALTER TABLE cpf_cache MODIFY prefeitura_id BIGINT UNSIGNED NULL');

        Schema::table('cpf_cache', function (Blueprint $table) {
            $table->unique('cpf', 'cpf_cache_cpf_unique');
            $table->index('prefeitura_id', 'cpf_cache_prefeitura_idx');
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cpf_cache')) {
            return;
        }

        Schema::table('cpf_cache', function (Blueprint $table) {
            try {
                $table->dropForeign(['prefeitura_id']);
            } catch (\Throwable $e) {
            }

            try {
                $table->dropUnique('cpf_cache_cpf_unique');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('cpf_cache_prefeitura_idx');
            } catch (\Throwable $e) {
            }
        });

        DB::statement('UPDATE cpf_cache SET prefeitura_id = 1 WHERE prefeitura_id IS NULL');
        DB::statement('ALTER TABLE cpf_cache MODIFY prefeitura_id BIGINT UNSIGNED NOT NULL');

        Schema::table('cpf_cache', function (Blueprint $table) {
            $table->unique(['prefeitura_id', 'cpf'], 'cpf_cache_prefeitura_cpf_unique');
            $table->index('cpf', 'cpf_cache_cpf_index');
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
        });
    }
};
