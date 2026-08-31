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
        Schema::table('landing_page_settings', function (Blueprint $table) {
            // Seção: O antes e o depois
            $table->string('section_benefits_title')->default('A evolução da sua Secretaria')->after('stats_papel');
            $table->text('section_benefits_subtitle')->nullable();
            
            // Seção: Como Funciona
            $table->string('section_how_it_works_title')->default('Como transformar sua Secretaria em 3 passos');
            $table->text('section_how_it_works_subtitle')->nullable();

            // Seção: Funcionalidades
            $table->string('section_features_title')->default('Recursos pensados para o esporte público');
            
            // Seção: Súmula Eletrônica
            $table->string('section_sumula_title')->default('Aposente o papel com a Súmula Eletrônica');
            $table->text('section_sumula_text')->nullable();

            // Footer
            $table->text('footer_about_text')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('social_instagram')->nullable();
            $table->string('social_facebook')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_page_settings', function (Blueprint $table) {
            $table->dropColumn([
                'section_benefits_title',
                'section_benefits_subtitle',
                'section_how_it_works_title',
                'section_how_it_works_subtitle',
                'section_features_title',
                'section_sumula_title',
                'section_sumula_text',
                'footer_about_text',
                'contact_email',
                'contact_phone',
                'social_instagram',
                'social_facebook'
            ]);
        });
    }
};
