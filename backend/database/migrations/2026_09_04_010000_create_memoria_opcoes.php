<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memoria_opcoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->string('grupo', 40);
            $table->string('valor', 80);
            $table->timestamps();

            $table->unique(['prefeitura_id', 'grupo', 'valor']);
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memoria_opcoes');
    }
};
