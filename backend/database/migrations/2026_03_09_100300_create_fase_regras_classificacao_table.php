<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fase_regras_classificacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prefeitura_id')->nullable()->constrained('prefeituras')->nullOnDelete();
            $table->foreignId('fase_id')->constrained('fases')->onDelete('cascade');
            $table->string('criterio', 60);
            $table->unsignedInteger('ordem')->default(1);
            $table->json('configuracao')->nullable();
            $table->timestamps();

            $table->index(['fase_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fase_regras_classificacao');
    }
};
