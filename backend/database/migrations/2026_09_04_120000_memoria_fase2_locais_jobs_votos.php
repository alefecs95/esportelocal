<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('memoria_locais', 'slug')) {
            Schema::table('memoria_locais', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('nome');
                $table->decimal('lat', 10, 7)->nullable()->after('endereco');
                $table->decimal('lng', 10, 7)->nullable()->after('lat');
                $table->string('foto_antes')->nullable()->after('foto');
                $table->string('foto_depois')->nullable()->after('foto_antes');
                $table->unsignedSmallInteger('ano_antes')->nullable()->after('foto_depois');
                $table->unsignedSmallInteger('ano_depois')->nullable()->after('ano_antes');
            });
        }

        $locais = DB::table('memoria_locais')->where(function ($q) {
            $q->whereNull('slug')->orWhere('slug', '');
        })->get();
        foreach ($locais as $local) {
            $base = Str::slug((string) $local->nome) ?: 'local';
            $slug = $base;
            $i = 1;
            while (
                DB::table('memoria_locais')
                    ->where('prefeitura_id', $local->prefeitura_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $local->id)
                    ->exists()
            ) {
                $slug = $base . '-' . (++$i);
            }
            DB::table('memoria_locais')->where('id', $local->id)->update(['slug' => $slug]);
        }

        Schema::table('memoria_locais', function (Blueprint $table) {
            $table->unique(['prefeitura_id', 'slug']);
        });

        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('memoria_votos_hall')) {
            Schema::create('memoria_votos_hall', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prefeitura_id');
                $table->foreignId('personalidade_id')->constrained('memoria_personalidades')->cascadeOnDelete();
                $table->string('fingerprint', 64);
                $table->timestamps();

                $table->unique(['prefeitura_id', 'personalidade_id', 'fingerprint'], 'memoria_votos_hall_unique');
                $table->foreign('prefeitura_id')->references('id')->on('prefeituras')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('memoria_votos_hall');
        Schema::table('memoria_locais', function (Blueprint $table) {
            $table->dropUnique(['prefeitura_id', 'slug']);
            $table->dropColumn(['slug', 'lat', 'lng', 'foto_antes', 'foto_depois', 'ano_antes', 'ano_depois']);
        });
    }
};
