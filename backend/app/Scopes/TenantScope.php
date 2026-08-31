<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Se a prefeitura estiver registrada no container, aplicamos o filtro
        if (App::has('current_tenant')) {
            $tenant = App::get('current_tenant');

            // Exceção para User: Permite ver o Super Admin mesmo que esteja em outro tenant (ID 1 geralmente)
            if ($model instanceof \App\Models\User) {
                $builder->where(function($query) use ($model, $tenant) {
                    $query->where($model->getTable() . '.prefeitura_id', $tenant->id)
                          ->orWhere($model->getTable() . '.perfil', 'SUPER_ADMIN');
                });
            } elseif ($model instanceof \App\Models\Modalidade) {
                 // Permite ver modalidades do próprio tenant OU da prefeitura padrão (ID 1)
                 $builder->where(function($query) use ($model, $tenant) {
                    $query->where($model->getTable() . '.prefeitura_id', $tenant->id)
                          ->orWhere($model->getTable() . '.prefeitura_id', 1);
                });
            } else {
                $builder->where($model->getTable() . '.prefeitura_id', $tenant->id);
            }
        }
    }
}
