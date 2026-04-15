<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Tables\Table;

/**
 * Единый паттерн пустых таблиц Filament (AF-018).
 *
 * Типы сценариев и тексты — в docs/reference/af-018-empty-state-contract.md
 */
final class AdminEmptyState
{
    /**
     * Подсказка для списков с фильтрами/поиском: данные могут быть, но не попали под условия.
     */
    public static function hintFiltersAndSearch(): string
    {
        return ' Если включены фильтры или поиск — список может быть пустым: сбросьте условия или измените запрос.';
    }

    /**
     * @param  array<int, Action>  $actions
     */
    public static function applyInitial(
        Table $table,
        string $heading,
        string $description,
        ?string $icon = null,
        array $actions = [],
    ): Table {
        $table = $table
            ->emptyStateHeading($heading)
            ->emptyStateDescription($description);

        if ($icon !== null) {
            $table = $table->emptyStateIcon($icon);
        }

        if ($actions !== []) {
            $table = $table->emptyStateActions($actions);
        }

        return $table;
    }
}
