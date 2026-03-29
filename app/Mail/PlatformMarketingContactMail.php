<?php

namespace App\Mail;

use App\Product\Settings\ProductMailSettingsResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class PlatformMarketingContactMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
        public ?int $crmRequestId = null,
    ) {
        if ($this->crmRequestId !== null) {
            $this->payload['crm_request_id'] = $this->crmRequestId;
        }
    }

    public function envelope(): Envelope
    {
        $mailSettings = app(ProductMailSettingsResolver::class);
        $intentLabel = (string) ($this->payload['intent_label'] ?? 'заявка');
        $brand = $mailSettings->platformBrandName();
        $fromEmail = $mailSettings->defaultFromAddress();
        $fromName = $mailSettings->defaultFromName();
        $replyTo = [];
        $email = $this->payload['email'] ?? null;
        $name = $this->payload['name'] ?? null;
        if (is_string($email) && $email !== '') {
            $replyTo[] = new Address($email, is_string($name) ? $name : '');
        }

        $crmPart = $this->crmRequestId !== null ? ' #'.$this->crmRequestId : '';

        $using = [];
        $using[] = function (Email $message): void {
            $message->getHeaders()->addTextHeader('X-Mail-Type', 'platform_contact_staff');
            $message->getHeaders()->addTextHeader('X-Mail-Group', 'crm_inbound');
            if ($this->crmRequestId !== null) {
                $message->getHeaders()->addTextHeader('X-Entity-Ref-ID', 'crm_request:'.$this->crmRequestId);
            }
        };

        return new Envelope(
            from: $fromEmail !== '' ? new Address($fromEmail, $fromName) : null,
            replyTo: $replyTo,
            subject: $brand.' — заявка с сайта'.$crmPart.': '.$intentLabel,
            using: $using,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.platform-marketing-contact-html',
            text: 'emails.platform-marketing-contact-text',
        );
    }
}
