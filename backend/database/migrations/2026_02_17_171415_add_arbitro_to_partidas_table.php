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
        Schema::table('partidas', function (Blueprint $table) {
            $table->foreignId('arbitro_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('placar_bloqueado')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partidas', function (Blueprint $table) {
            $table->dropForeign(['arbitro_id']);
            $table->dropColumn('arbitro_id');
            $table->dropColumn('placar_bloqueado');
        });
    }
};
