<?php

namespace App\PageBuilder\Blueprints;

use App\PageBuilder\PageSectionCategory;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class ContactsInfoSectionBlueprint extends AbstractPageSectionBlueprint
{
    public function id(): string
    {
        return 'contacts_info';
    }

    public function label(): string
    {
        return 'Контакты';
    }

    public function description(): string
    {
        return 'Телефон, email, мессенджеры, адрес, режим, карта.';
    }

    public function icon(): string
    {
        return 'heroicon-o-map-pin';
    }

    public function category(): PageSectionCategory
    {
        return PageSectionCategory::Contacts;
    }

    public function defaultData(): array
    {
        return [
            'title' => 'Контакты',
            'description' => null,
            'phone' => null,
            'email' => null,
            'whatsapp' => null,
            'telegram' => null,
            'address' => null,
            'working_hours' => null,
            'map_embed' => null,
            'map_link' => null,
        ];
    }

    public function formComponents(): array
    {
        return [
            TextInput::make('data_json.title')
                ->label('Заголовок')
                ->maxLength(255)
                ->columnSpanFull(),
            Textarea::make('data_json.description')
                ->label('Вводный текст')
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('data_json.phone')
                ->label('Телефон')
                ->tel()
                ->maxLength(64),
            TextInput::make('data_json.email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            TextInput::make('data_json.whatsapp')
                ->label('WhatsApp')
                ->maxLength(255),
            TextInput::make('data_json.telegram')
                ->label('Telegram')
                ->maxLength(255),
            Textarea::make('data_json.address')
                ->label('Адрес')
                ->rows(2)
                ->columnSpanFull(),
            Textarea::make('data_json.working_hours')
                ->label('Режим работы')
                ->rows(2)
                ->columnSpanFull(),
            Textarea::make('data_json.map_embed')
                ->label('Карта (HTML iframe, опционально)')
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('data_json.map_link')
                ->label('Ссылка на карту')
                ->url()
                ->maxLength(2048),
        ];
    }

    public function viewLogicalName(): string
    {
        return 'sections.contacts-info';
    }

    public function previewSummary(array $data): string
    {
        $phone = trim((string) ($data['phone'] ?? ''));
        $addr = $this->stringPreview($data, 'address', 40);
        $email = trim((string) ($data['email'] ?? ''));
        $parts = array_filter([$phone, $addr, $email], fn (string $s): bool => $s !== '');

        return $parts !== [] ? implode(' · ', $parts) : 'Контакты';
    }
}
