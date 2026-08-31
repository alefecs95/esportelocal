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
        Schema::create('landing_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hero_title')->default('O sistema definitivo para a Gestão Esportiva');
            $table->text('hero_subtitle')->nullable();
            $table->string('video_url')->nullable();
            $table->string('stats_cidades')->default('40+');
            $table->string('stats_atletas')->default('150k');
            $table->string('stats_jogos')->default('12k');
            $table->string('stats_papel')->default('Zero');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_settings');
    }
};
