<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campeonato_categorias', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_categorias', 'ordem_premiacao')) {
                $table->unsignedInteger('ordem_premiacao')->nullable()->after('largada_prevista');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campeonato_categorias', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_categorias', 'ordem_premiacao')) {
                $table->dropColumn('ordem_premiacao');
            }
        });
    }
};
