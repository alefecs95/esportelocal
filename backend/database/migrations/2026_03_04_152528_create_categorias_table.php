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
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prefeitura_id')->constrained('prefeituras')->onDelete('cascade');
            $table->string('nome');
            $table->string('slug');
            $table->string('cor')->nullable(); // Hex color
            $table->text('descricao')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Unique slug per tenant
            $table->unique(['prefeitura_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
