<?php

namespace App\Services;

use App\Repositories\CampeonatoRepository;
use App\Models\Campeonato;
use App\Models\Atleta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

class CampeonatoService
{
    protected $campeonatoRepository;

    public function __construct(CampeonatoRepository $campeonatoRepository)
    {
        $this->campeonatoRepository = $campeonatoRepository;
    }

    public function getAll(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->campeonatoRepository->getAll($filters, $perPage);
    }

    public function create(array $data): Campeonato
    {
        // Se não informar temporada, usar temporada ativa
        if (empty($data['temporada_id'])) {
            $temporadaAtiva = \App\Models\Temporada::where('status', 'ativa')->first();
            if ($temporadaAtiva) {
                $data['temporada_id'] = $temporadaAtiva->id;
            }
        }
        
        if (array_key_exists('cartoes_amarelos_para_suspensao', $data) && $data['cartoes_amarelos_para_suspensao'] === null) {
            $data['cartoes_amarelos_para_suspensao'] = 2;
        }

        $campeonato = $this->campeonatoRepository->create($data);

        // Salvar categorias se fornecidas
        if (isset($data['categorias']) && is_array($data['categorias'])) {
            foreach ($data['categorias'] as $catData) {
                $campeonato->categorias()->create($catData);
            }
        }

        return $campeonato;
    }

    public function find(int $id): ?Campeonato
    {
        $campeonato = $this->campeonatoRepository->find($id);
        if (!$campeonato) {
            throw new Exception("Campeonato não encontrado.");
        }
        // Carregar categorias
        $campeonato->load('categorias');
        return $campeonato;
    }

    public function update(int $id, array $data): Campeonato
    {
        $campeonato = $this->find($id);
        
        if (array_key_exists('cartoes_amarelos_para_suspensao', $data) && $data['cartoes_amarelos_para_suspensao'] === null) {
            $data['cartoes_amarelos_para_suspensao'] = $campeonato->cartoes_amarelos_para_suspensao ?? 2;
        }

        $this->campeonatoRepository->update($campeonato, $data);
        
        // Atualizar categorias (Sync: remove missing, update existing, create new)
        if (isset($data['categorias']) && is_array($data['categorias'])) {
            // Get IDs from input to keep
            $keepIds = [];
            foreach ($data['categorias'] as $catData) {
                if (isset($catData['id'])) {
                    $keepIds[] = $catData['id'];
                    $cat = $campeonato->categorias()->find($catData['id']);
                    if ($cat) {
                        $cat->update($catData);
                    }
                } else {
                    // Create new
                    $newCat = $campeonato->categorias()->create($catData);
                    $keepIds[] = $newCat->id;
                }
            }
            // Delete removed categories
            $campeonato->categorias()->whereNotIn('id', $keepIds)->delete();
        }

        $campeonato->refresh();
        $campeonato->load('categorias');

        if ($campeonato->status === 'finalizado') {
            // $this->rankingService->atualizarRanking($campeonato); // Uncomment when ranking service injected
        }
        
        return $campeonato;
    }

    public function delete(int $id): bool
    {
        $campeonato = $this->find($id);
        return $this->campeonatoRepository->delete($campeonato);
    }

    public function verificarElegibilidade(int $atletaId, int $campeonatoId): array
    {
        $atleta = Atleta::findOrFail($atletaId);
        $campeonato = Campeonato::findOrFail($campeonatoId);

        $elegivel = true;
        $motivos = [];

        // 1. Idade
        $idade = \Carbon\Carbon::parse($atleta->data_nascimento)->age;
        if (($campeonato->idade_min && $idade < $campeonato->idade_min) || 
            ($campeonato->idade_max && $idade > $campeonato->idade_max)) {
            $elegivel = false;
            $motivos[] = "Idade ($idade) fora do permitido ({$campeonato->idade_min}-{$campeonato->idade_max}).";
        }

        // 2. Gênero
        $generoAtleta = strtolower((string) ($atleta->sexo ?? ''));
        if ($campeonato->genero !== 'misto' && $generoAtleta !== $campeonato->genero) {
            $elegivel = false;
            $motivos[] = "Gênero do atleta ({$generoAtleta}) incompatível com a competição ({$campeonato->genero}).";
        }

        // 3. Localização (Cidade/Bairro)
        if ($campeonato->cidade_eleitoral && $atleta->cidade_eleitoral !== $campeonato->cidade_eleitoral) {
            $elegivel = false;
            $motivos[] = "Cidade eleitoral não permitida.";
        }
        
        if ($campeonato->bairro_eleitoral && $atleta->bairro_eleitoral !== $campeonato->bairro_eleitoral) {
            $elegivel = false;
            $motivos[] = "Bairro eleitoral não permitido.";
        }

        return [
            'elegivel' => $elegivel,
            'motivos' => $motivos,
            'atleta' => $atleta->only(['id', 'nome']),
            'campeonato' => $campeonato->only(['id', 'nome'])
        ];
    }

    public function simularElegiveis(array $criteria, bool $returnList = false)
    {
        $query = Atleta::query();

        if (!empty($criteria['cidade_eleitoral'])) {
            $query->where('cidade_eleitoral', $criteria['cidade_eleitoral']);
        }

        if (!empty($criteria['bairro_eleitoral'])) {
            $query->where('bairro_eleitoral', $criteria['bairro_eleitoral']);
        }

        // Validação de Idade (precisa ser robusta)
        // Se ambos idade_min e idade_max forem nulos/zero, não filtra por idade (permite todos)
        if (!empty($criteria['idade_min']) || !empty($criteria['idade_max'])) {
            $min = !empty($criteria['idade_min']) ? (int)$criteria['idade_min'] : 0;
            $max = !empty($criteria['idade_max']) ? (int)$criteria['idade_max'] : 150; // Aumentado para cobrir qualquer idade
            
            // TIMESTAMPDIFF é específico do MySQL.
            $query->whereRaw("TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN ? AND ?", [$min, $max]);
        }
        
        // Filtro por Gênero (se aplicável)
        if (!empty($criteria['genero']) && $criteria['genero'] !== 'misto') {
            $query->whereRaw('LOWER(sexo) = ?', [strtolower((string) $criteria['genero'])]);
        }

        if ($returnList) {
            return $query->select('id', 'nome', 'sexo', 'data_nascimento', 'cidade_eleitoral', 'bairro_eleitoral')
                         ->orderBy('nome')
                         ->get()
                         ->map(function($atleta) {
                             $atleta->idade = $atleta->data_nascimento ? \Carbon\Carbon::parse($atleta->data_nascimento)->age : null;
                             return $atleta;
                         });
        }

        return $query->count();
    }
}
