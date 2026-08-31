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
        if (!Schema::hasColumn('entidades', 'logo')) {
            Schema::table('entidades', function (Blueprint $table) {
                $table->string('logo')->nullable()->after('sigla');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
