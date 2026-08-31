<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppNotification extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'whatsapp_notifications';

    protected $fillable = [
        'prefeitura_id',
        'user_id',
        'phone',
        'receive_notifications',
        'unsubscribe_token',
        'last_notified_at',
    ];

    protected $casts = [
        'receive_notifications' => 'boolean',
        'last_notified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
