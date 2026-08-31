<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Temporada extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'prefeitura_id',
        'nome',
        'ano',
        'status',
    ];

    public function campeonatos()
    {
        return $this->hasMany(Campeonato::class);
    }
}
