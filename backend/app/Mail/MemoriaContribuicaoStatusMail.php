<?php

namespace App\Mail;

use App\Models\MemoriaAcervo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemoriaContribuicaoStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MemoriaAcervo $item,
        public bool $aprovado,
        public ?string $motivo = null
    ) {
    }

    public function envelope(): Envelope
    {
        $status = $this->aprovado ? 'aprovada' : 'não publicada';
        return new Envelope(
            subject: 'Sua memória foi ' . $status . ': ' . $this->item->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.memoria-contribuicao-status',
        );
    }
}
