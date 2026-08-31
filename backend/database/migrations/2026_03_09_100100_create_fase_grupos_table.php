<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fase_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prefeitura_id')->nullable()->constrained('prefeituras')->nullOnDelete();
            $table->foreignId('fase_id')->constrained('fases')->onDelete('cascade');
            $table->string('nome', 20);
            $table->unsignedInteger('ordem')->default(1);
            $table->json('configuracao')->nullable();
            $table->timestamps();

            $table->unique(['fase_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fase_grupos');
    }
};
