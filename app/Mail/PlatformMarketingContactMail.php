<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformMarketingContactMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function envelope(): Envelope
    {
        $intentLabel = (string) ($this->payload['intent_label'] ?? 'заявка');
        $replyTo = [];
        $email = $this->payload['email'] ?? null;
        $name = $this->payload['name'] ?? null;
        if (is_string($email) && $email !== '') {
            $replyTo[] = new Address($email, is_string($name) ? $name : '');
        }

        return new Envelope(
            subject: 'RentBase — заявка с сайта: '.$intentLabel,
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.platform-marketing-contact-text',
        );
    }
}
