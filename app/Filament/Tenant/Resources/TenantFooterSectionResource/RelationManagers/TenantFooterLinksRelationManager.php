<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\TenantFooterSectionResource\RelationManagers;

use App\Models\TenantFooterLink;
use App\Tenant\Footer\FooterLimits;
use App\Tenant\Footer\FooterSectionType;
use App\Tenant\Footer\TenantFooterLinkKind;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class TenantFooterLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'links';

    protected static ?string $title = 'Ссылки секции';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ($ownerRecord->type ?? '') === FooterSectionType::LINK_GROUPS;
    }

    public function form(Schema $schema): Schema
    {
        $kinds = [];
        foreach (TenantFooterLinkKind::cases() as $c) {
            $kinds[$c->value] = match ($c) {
                TenantFooterLinkKind::Internal => 'Внутренняя',
                TenantFooterLinkKind::External => 'Внешняя',
                TenantFooterLinkKind::Phone => 'Телефон',
                TenantFooterLinkKind::Email => 'Email',
                TenantFooterLinkKind::Telegram => 'Telegram',
                TenantFooterLinkKind::Whatsapp => 'WhatsApp',
                TenantFooterLinkKind::Document => 'Документ',
            };
        }

        return $schema
            ->components([
                Section::make('Ссылка')
                    ->schema([
                        TextInput::make('group_key')
                            ->label('Ключ группы')
                            ->maxLength(64)
                            ->helperText('Одинаковый ключ объединяет ссылки в одну группу. Пусто — группа «по умолчанию».'),
                        TextInput::make('label')
                            ->label('Подпись')
                            ->required()
                            ->maxLength(FooterLimits::SHORT_FIELD_MAX),
                        TextInput::make('url')
                            ->label('URL / телефон / email')
                            ->required()
                            ->maxLength(2048),
                        Select::make('link_kind')
                            ->label('Тип')
                            ->options($kinds)
                            ->required()
                            ->native(true),
                        TextInput::make('target')
                            ->label('target')
                            ->placeholder('_self или _blank')
                            ->maxLength(16),
                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_enabled')
                            ->label('Включено')
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group_key')->label('Группа')->placeholder('—'),
                TextColumn::make('label')->label('Подпись')->searchable(),
                TextColumn::make('link_kind')->label('Тип')->badge(),
                IconColumn::make('is_enabled')->label('Вкл')->boolean(),
                TextColumn::make('sort_order')->label('Порядок'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data): Model {
                        $section = $this->getOwnerRecord();
                        $count = TenantFooterLink::query()->where('section_id', $section->id)->count();
                        if ($count >= FooterLimits::MAX_LINKS_PER_SECTION) {
                            throw ValidationException::withMessages([
                                'url' => 'В секции не более '.FooterLimits::MAX_LINKS_PER_SECTION.' ссылок.',
                            ]);
                        }
                        $gk = trim((string) ($data['group_key'] ?? ''));
                        if ($gk !== '') {
                            $inGroup = TenantFooterLink::query()
                                ->where('section_id', $section->id)
                                ->where('group_key', $gk)
                                ->count();
                            if ($inGroup >= FooterLimits::LINK_GROUP_MAX_LINKS) {
                                throw ValidationException::withMessages([
                                    'group_key' => 'В группе не более '.FooterLimits::LINK_GROUP_MAX_LINKS.' ссылок.',
                                ]);
                            }
                        }

                        return TenantFooterLink::query()->create(array_merge($data, [
                            'section_id' => $section->id,
                        ]));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
