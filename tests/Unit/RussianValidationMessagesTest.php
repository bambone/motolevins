<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RussianValidationMessagesTest extends TestCase
{
    #[Test]
    public function validation_required_is_russian_when_locale_is_ru(): void
    {
        $previous = app()->getLocale();
        app()->setLocale('ru');

        try {
            $line = __('validation.required', ['attribute' => 'Email']);
            $this->assertStringContainsString('обязательно', $line);
            $this->assertStringContainsString('Email', $line);
            $this->assertStringNotContainsString('required', strtolower($line));
        } finally {
            app()->setLocale($previous);
        }
    }

    #[Test]
    public function validation_in_is_russian_when_locale_is_ru(): void
    {
        $previous = app()->getLocale();
        app()->setLocale('ru');

        try {
            $this->assertSame('Выбрано некорректное значение.', __('validation.in'));
        } finally {
            app()->setLocale($previous);
        }
    }

    #[Test]
    public function auth_failed_is_russian_when_locale_is_ru(): void
    {
        $previous = app()->getLocale();
        app()->setLocale('ru');

        try {
            $this->assertStringContainsString('Неверное', __('auth.failed'));
        } finally {
            app()->setLocale($previous);
        }
    }
}
