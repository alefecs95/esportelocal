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
        Schema::table('noticias', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('titulo');
            $table->text('meta_description')->nullable()->after('conteudo');
            $table->integer('visualizacoes')->default(0)->after('publicado_em');
            
            // Unique slug per tenant logic handled in code, but simple unique index here if needed.
            // Since we have SoftDeletes and Tenant, unique constraint is tricky in migration without raw SQL.
            // We'll enforce it in application logic or composite index.
            $table->index(['slug', 'prefeitura_id']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('noticias', function (Blueprint $table) {
            $table->dropColumn(['slug', 'meta_description', 'visualizacoes']);
        });
    }
};
