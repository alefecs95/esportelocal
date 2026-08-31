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
        Schema::create('equipe_atleta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipe_id')->constrained()->onDelete('cascade');
            $table->foreignId('atleta_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['equipe_id', 'atleta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipe_atleta');
    }
};
