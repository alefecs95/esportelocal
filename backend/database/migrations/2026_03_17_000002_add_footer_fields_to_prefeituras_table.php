<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prefeituras', function (Blueprint $table) {
            $table->string('logo_rodape')->nullable()->after('logo');
            $table->string('footer_titulo')->nullable()->after('email_contato');
            $table->string('footer_link_transparencia')->nullable()->after('footer_titulo');
            $table->string('footer_link_editais')->nullable()->after('footer_link_transparencia');
            $table->string('footer_link_contato')->nullable()->after('footer_link_editais');
        });
    }

    public function down(): void
    {
        Schema::table('prefeituras', function (Blueprint $table) {
            $table->dropColumn([
                'logo_rodape',
                'footer_titulo',
                'footer_link_transparencia',
                'footer_link_editais',
                'footer_link_contato',
            ]);
        });
    }
};
