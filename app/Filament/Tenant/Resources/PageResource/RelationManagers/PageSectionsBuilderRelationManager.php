<?php

namespace App\Filament\Tenant\Resources\PageResource\RelationManagers;

use App\Livewire\Tenant\PageSectionsBuilder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PageSectionsBuilderRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Секции страницы';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereRaw('0 = 1'))
            ->columns([
                TextColumn::make('id')->label('')->hidden(),
            ])
            ->paginated(false)
            ->heading(null);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Schema `getRecord()` is null here; nested Livewire must receive the owner Page explicitly.
                Livewire::make(PageSectionsBuilder::class, fn (): array => [
                    'record' => $this->getOwnerRecord(),
                ])->key(fn (): string => 'page-sections-builder-'.$this->getOwnerRecord()->getKey()),
            ]);
    }
}
