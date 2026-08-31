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
        Schema::create('prefeituras', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique(); // subdomínio
            $table->string('dominio')->nullable()->unique(); // domínio customizado opcional
            $table->string('logo')->nullable();
            $table->string('cor_primaria')->default('#000000');
            $table->string('cor_secundaria')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prefeituras');
    }
};
