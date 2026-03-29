<?php

namespace App\Filament\Shared\CRM;

use App\Models\CrmRequest;
use Filament\Tables\Columns\TextColumn;

final class CrmSharedTable
{
    /**
     * @return array<int, TextColumn>
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('id')
                ->label('ID')
                ->sortable(),
            TextColumn::make('created_at')
                ->label('Создана')
                ->dateTime('d.m.Y H:i')
                ->sortable(),
            TextColumn::make('name')
                ->label('Имя')
                ->searchable()
                ->sortable(),
            TextColumn::make('email')
                ->label('Email')
                ->searchable(),
            TextColumn::make('phone')
                ->label('Телефон')
                ->searchable(),
            TextColumn::make('request_type')
                ->label('Тип')
                ->badge()
                ->toggleable(),
            TextColumn::make('source')
                ->label('Источник')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('status')
                ->label('Статус CRM')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => $state ? (CrmRequest::statusLabels()[$state] ?? $state) : '—'),
        ];
    }
}
