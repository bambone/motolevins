<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

final class UserPasswordFormFields
{
    public static function generateSuffixAction(string $statePath): Action
    {
        return Action::make('generate_password_'.$statePath)
            ->label('Сгенерировать')
            ->icon('heroicon-m-arrow-path')
            ->color('gray')
            ->action(function (Set $set) use ($statePath): void {
                $set($statePath, Str::password(20));
            });
    }

    public static function createPasswordInput(): TextInput
    {
        return TextInput::make('password')
            ->label('Пароль')
            ->password()
            ->revealable()
            ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
            ->dehydrated(fn ($state) => filled($state))
            ->required(fn (string $operation): bool => $operation === 'create')
            ->maxLength(255)
            ->suffixAction(self::generateSuffixAction('password'))
            ->helperText('Можно сгенерировать кнопкой справа и при необходимости скопировать до сохранения.');
    }

    public static function editPasswordSection(): Section
    {
        return Section::make('Пароль')
            ->description(FilamentInlineMarkdown::toHtml(
                'Укажите **новый пароль** и сохраните форму — он будет установлен для пользователя. '.
                'На email пользователя уйдёт письмо с паролем и напоминанием **сменить его при следующем входе** и **удалить письмо** из почты. '.
                'Поле можно оставить пустым, чтобы не менять пароль.'
            ))
            ->visibleOn('edit')
            ->collapsed()
            ->collapsible()
            ->schema([
                TextInput::make('new_password')
                    ->label('Новый пароль')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->suffixAction(self::generateSuffixAction('new_password')),
            ]);
    }
}
