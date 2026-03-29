<?php

namespace Tests\Feature;

use App\Mail\AdminIssuedPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminIssuedPasswordMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailable_includes_plain_password_in_body(): void
    {
        $user = User::factory()->make([
            'name' => 'Тест',
            'email' => 'user@example.com',
        ]);

        $mail = new AdminIssuedPasswordMail($user, 'GeneratedSecret99');

        $this->assertStringContainsString('GeneratedSecret99', $mail->render());
    }

    public function test_mail_can_be_sent_via_facade(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'recipient@example.com',
        ]);

        Mail::to($user->email)->send(new AdminIssuedPasswordMail($user, 'Plain123456'));

        Mail::assertSent(AdminIssuedPasswordMail::class, function (AdminIssuedPasswordMail $mail): bool {
            return $mail->plainPassword === 'Plain123456';
        });
    }
}
