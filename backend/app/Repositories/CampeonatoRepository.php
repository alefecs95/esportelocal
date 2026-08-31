<?php

namespace App\Repositories;

use App\Models\Campeonato;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CampeonatoRepository
{
    public function getAll(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Campeonato::with('modalidade');

        if (!empty($filters['modalidade_id'])) {
            $query->where('modalidade_id', $filters['modalidade_id']);
        }

        if (!empty($filters['temporada_id'])) {
            $query->where('temporada_id', $filters['temporada_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['categoria'])) {
            $query->where('categoria', 'like', '%' . $filters['categoria'] . '%');
        }

        if (!empty($filters['data_inicio'])) {
            $query->whereDate('data_inicio', '>=', $filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $query->whereDate('data_fim', '<=', $filters['data_fim']);
        }
        
        // Ordenação padrão por data de início mais recente
        $query->orderBy('data_inicio', 'desc');

        return $query->paginate($perPage);
    }

    public function create(array $data): Campeonato
    {
        return Campeonato::create($data);
    }

    public function find(int $id): ?Campeonato
    {
        return Campeonato::with('modalidade')->find($id);
    }

    public function update(Campeonato $campeonato, array $data): bool
    {
        return $campeonato->update($data);
    }

    public function delete(Campeonato $campeonato): bool
    {
        return $campeonato->delete();
    }
}
