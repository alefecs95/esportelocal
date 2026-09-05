<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cpf',
        'perfil',
        'status',
        'forcar_troca_senha',
        'prefeitura_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['is_manager', 'is_atleta'];

    public function entidades()
    {
        return $this->hasMany(Entidade::class);
    }

    public function getIsManagerAttribute()
    {
        return $this->perfil === 'RESPONSAVEL' || $this->entidades()->exists();
    }

    public function getIsAtletaAttribute()
    {
        return $this->perfil === 'ATLETA';
    }

    public function isSuperAdmin()
    {
        return $this->perfil === 'SUPER_ADMIN';
    }

    public function isTenantAdmin(): bool
    {
        return in_array($this->perfil, ['ADMIN', 'SUPER_ADMIN'], true);
    }
}
