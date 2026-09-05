<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MemoriaVotoHall extends Model
{
    use BelongsToTenant;

    protected $table = 'memoria_votos_hall';

    protected $fillable = [
        'prefeitura_id',
        'personalidade_id',
        'fingerprint',
    ];

    public function personalidade()
    {
        return $this->belongsTo(MemoriaPersonalidade::class, 'personalidade_id');
    }
}
