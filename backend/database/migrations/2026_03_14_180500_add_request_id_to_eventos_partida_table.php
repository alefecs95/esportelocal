<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos_partida', function (Blueprint $table) {
            $table->string('request_id', 100)->nullable()->after('time_id');
            $table->unique(['partida_id', 'request_id']);
        });
    }

    public function down(): void
    {
        Schema::table('eventos_partida', function (Blueprint $table) {
            $table->dropUnique('eventos_partida_partida_id_request_id_unique');
            $table->dropColumn('request_id');
        });
    }
};
