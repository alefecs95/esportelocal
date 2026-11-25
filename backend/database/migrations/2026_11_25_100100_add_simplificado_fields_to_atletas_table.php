<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE atletas MODIFY COLUMN cpf VARCHAR(255) NULL');
            DB::statement('ALTER TABLE atletas MODIFY COLUMN nome_mae VARCHAR(255) NULL');
            DB::statement('ALTER TABLE atletas MODIFY COLUMN titulo_eleitor VARCHAR(255) NULL');
            DB::statement('ALTER TABLE atletas MODIFY COLUMN cep VARCHAR(255) NULL');
            DB::statement('ALTER TABLE atletas MODIFY COLUMN endereco VARCHAR(255) NULL');
            DB::statement('ALTER TABLE atletas MODIFY COLUMN bairro VARCHAR(255) NULL');
            DB::statement('ALTER TABLE atletas MODIFY COLUMN cidade VARCHAR(255) NULL');
            DB::statement('ALTER TABLE atletas MODIFY COLUMN estado VARCHAR(255) NULL');
        }

        Schema::table('atletas', function (Blueprint $table) {
            if (!Schema::hasColumn('atletas', 'responsavel_nome')) {
                $table->string('responsavel_nome')->nullable()->after('nome_pai');
            }
            if (!Schema::hasColumn('atletas', 'responsavel_cpf')) {
                $table->string('responsavel_cpf', 14)->nullable()->after('responsavel_nome');
            }
            if (!Schema::hasColumn('atletas', 'responsavel_telefone')) {
                $table->string('responsavel_telefone', 20)->nullable()->after('responsavel_cpf');
            }
            if (!Schema::hasColumn('atletas', 'tamanho_camiseta')) {
                $table->string('tamanho_camiseta', 10)->nullable()->after('telefone');
            }
            if (!Schema::hasColumn('atletas', 'contato_emergencia')) {
                $table->string('contato_emergencia', 255)->nullable()->after('tamanho_camiseta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('atletas', function (Blueprint $table) {
            $cols = array_filter([
                'responsavel_nome',
                'responsavel_cpf',
                'responsavel_telefone',
                'tamanho_camiseta',
                'contato_emergencia',
            ], fn ($col) => Schema::hasColumn('atletas', $col));

            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
