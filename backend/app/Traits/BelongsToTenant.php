<?php

namespace App\Traits;

use App\Models\Prefeitura;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;

trait BelongsToTenant
{
    /**
     * The "booted" method of the model.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (App::has('current_tenant') && !$model->prefeitura_id) {
                $model->prefeitura_id = App::get('current_tenant')->id;
            }
        });
    }

    public function prefeitura(): BelongsTo
    {
        return $this->belongsTo(Prefeitura::class);
    }
}
