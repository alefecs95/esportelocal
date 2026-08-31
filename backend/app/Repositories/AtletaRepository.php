<?php

namespace App\Repositories;

use App\Models\Atleta;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AtletaRepository
{
    public function getAll(int $perPage = 10, ?string $search = null, array $filters = []): LengthAwarePaginator
    {
        $query = Atleta::query();

        if ($search) {
            $search = trim($search);
            $cpfSearch = preg_replace('/\D/', '', $search);

            $query->where(function ($q) use ($search, $cpfSearch) {
                $q->where('nome', 'like', "%{$search}%");

                if ($cpfSearch !== '') {
                    $q->orWhere('cpf', 'like', "%{$cpfSearch}%")
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') LIKE ?", ["%{$cpfSearch}%"]);
                }
            });
        }

        if (array_key_exists('acesso_ativo', $filters) && $filters['acesso_ativo'] !== null) {
            $query->where('acesso_ativo', (bool) $filters['acesso_ativo']);
        }

        if (array_key_exists('pode_participar_competicoes', $filters) && $filters['pode_participar_competicoes'] !== null) {
            $query->where('pode_participar_competicoes', (bool) $filters['pode_participar_competicoes']);
        }

        if (!empty($filters['cidade'])) {
            $query->where('cidade', 'like', '%' . trim($filters['cidade']) . '%');
        }

        if (array_key_exists('titulo_verificado', $filters) && $filters['titulo_verificado'] !== null) {
            $query->where('titulo_verificado', (bool) $filters['titulo_verificado']);
        }

        if (!empty($filters['idade_min'])) {
            $query->whereDate('data_nascimento', '<=', Carbon::now()->subYears((int) $filters['idade_min'])->toDateString());
        }

        if (!empty($filters['idade_max'])) {
            $query->whereDate('data_nascimento', '>=', Carbon::now()->subYears((int) $filters['idade_max'] + 1)->addDay()->toDateString());
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function create(array $data): Atleta
    {
        return Atleta::create($data);
    }

    public function find(int $id): ?Atleta
    {
        return Atleta::find($id);
    }

    public function update(Atleta $atleta, array $data): bool
    {
        return $atleta->update($data);
    }

    public function delete(Atleta $atleta): bool
    {
        return $atleta->delete();
    }
}
