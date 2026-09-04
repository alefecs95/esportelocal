<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memoria_acervo', function (Blueprint $table) {
            $table->string('alt_text')->nullable()->after('titulo');
            $table->text('historia')->nullable()->after('descricao');
            $table->string('fonte_tipo')->nullable()->after('fonte');
            $table->string('doacao')->nullable()->after('fonte_tipo');
        });

        Schema::table('memoria_marcos', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('ordem')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('memoria_personalidades', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('publicado')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });

        Schema::create('memoria_auditoria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao');
            $table->string('entidade_tipo');
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->string('resumo', 500)->nullable();
            $table->timestamps();

            $table->index(['prefeitura_id', 'created_at']);
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
        });

        Schema::create('memoria_notificacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prefeitura_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo')->default('envio');
            $table->string('titulo');
            $table->text('mensagem')->nullable();
            $table->string('link')->nullable();
            $table->boolean('lida')->default(false);
            $table->timestamp('lida_em')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'lida']);
            $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memoria_notificacoes');
        Schema::dropIfExists('memoria_auditoria');

        Schema::table('memoria_personalidades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
        });
        Schema::table('memoria_marcos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
        });
        Schema::table('memoria_acervo', function (Blueprint $table) {
            $table->dropColumn(['alt_text', 'historia', 'fonte_tipo', 'doacao']);
        });
    }
};
