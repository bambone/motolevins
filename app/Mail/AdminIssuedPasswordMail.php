<?php

namespace App\Mail;

use App\Contracts\Mail\DefinesTenantMailMetadata;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminIssuedPasswordMail extends Mailable implements DefinesTenantMailMetadata
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Новый пароль для входа — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.admin-issued-password-text',
        );
    }

    public function tenantMailType(): string
    {
        return 'admin_issued_password';
    }

    public function tenantMailGroup(): string
    {
        return 'auth';
    }
}
