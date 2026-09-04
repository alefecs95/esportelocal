<?php

namespace App\Mail;

use App\Models\MemoriaAcervo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemoriaNovoEnvioMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MemoriaAcervo $item)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Novo envio para a Memória do Esporte: ' . $this->item->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.memoria-novo-envio',
            with: [
                'url' => rtrim((string) env('FRONTEND_URL', config('app.url')), '/') . '/admin/memoria',
            ],
        );
    }
}
