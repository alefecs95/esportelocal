<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppNotificationRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $phone,
        public string $type,
        public string $message,
        public ?int $userId = null,
        public bool $force = false,
        public ?int $tenantId = null
    ) {
    }
}
