<?php

namespace App\Filament\Concerns;

use App\Mail\AdminIssuedPasswordMail;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

trait IssuesNewUserPasswordFromForm
{
    protected ?string $pendingIssuedPasswordPlain = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergeNewPasswordIntoUserData(array $data): array
    {
        $this->pendingIssuedPasswordPlain = null;

        if (filled($data['new_password'] ?? null)) {
            $plain = (string) $data['new_password'];
            if (strlen($plain) < 8) {
                throw ValidationException::withMessages([
                    'data.new_password' => 'Новый пароль должен быть не короче 8 символов.',
                ]);
            }
            $this->pendingIssuedPasswordPlain = $plain;
            $data['password'] = $plain;
        }

        unset($data['new_password']);

        return $data;
    }

    protected function sendIssuedPasswordMailIfNeeded(): void
    {
        if ($this->pendingIssuedPasswordPlain === null) {
            return;
        }

        $plain = $this->pendingIssuedPasswordPlain;
        $this->pendingIssuedPasswordPlain = null;

        try {
            Mail::to($this->record->email)->send(
                new AdminIssuedPasswordMail($this->record, $plain)
            );
        } catch (Throwable $e) {
            report($e);
            Notification::make()
                ->title('Пароль сохранён')
                ->body('Не удалось отправить письмо на email пользователя. Передайте пароль безопасным каналом.')
                ->warning()
                ->send();
        }
    }
}
