<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactMessage;

class NewContactAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $contactMessage;

    // Recibimos el mensaje recién creado
    public function __construct(ContactMessage $contactMessage)
    {
        $this->contactMessage = $contactMessage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚨 Nueva Cotización B2B - Amazon Nuts',
        );
    }

    public function content(): Content
    {
        // Le decimos qué diseño HTML usar
        return new Content(
            view: 'emails.new_contact_alert',
        );
    }
}