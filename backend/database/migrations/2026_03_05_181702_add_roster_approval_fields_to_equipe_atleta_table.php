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
        Schema::table('equipe_atleta', function (Blueprint $table) {
            $table->enum('status', ['pendente', 'aprovado', 'rejeitado'])->default('aprovado')->after('atleta_id');
            $table->foreignId('aprovado_por')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('aprovado_em')->nullable()->after('aprovado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipe_atleta', function (Blueprint $table) {
            $table->dropForeign(['aprovado_por']);
            $table->dropColumn(['status', 'aprovado_por', 'aprovado_em']);
        });
    }
};
