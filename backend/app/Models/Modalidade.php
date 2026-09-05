<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Modalidade extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'prefeitura_id',
        'nome',
        'tipo',
        'descricao',
        'status',
        'regras_config',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function getRegrasConfigAttribute($value)
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    public function setRegrasConfigAttribute($value): void
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['regras_config'] = json_encode($value);
            return;
        }

        $this->attributes['regras_config'] = $value ?: null;
    }

    public function atletas()
    {
        return $this->belongsToMany(Atleta::class, 'atleta_modalidade');
    }

    public function campeonatos()
    {
        return $this->hasMany(Campeonato::class);
    }
}
