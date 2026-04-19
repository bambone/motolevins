<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Support;

use App\Models\SchedulingTarget;

/**
 * Синхронизация pivot scheduling_target_resource с предсказуемыми дефолтами и сохранением существующих атрибутов при edit.
 */
final class SchedulingTargetResourceAttachmentSync
{
    /**
     * @return array{priority: int, is_default: bool, assignment_strategy: string}
     */
    public static function defaultPivotRow(): array
    {
        return [
            'priority' => 0,
            'is_default' => false,
            'assignment_strategy' => 'first_available',
        ];
    }

    /**
     * @param  list<int>  $orderedResourceIds
     */
    public static function syncWithDefaultPivot(SchedulingTarget $target, array $orderedResourceIds): void
    {
        $sync = [];
        foreach ($orderedResourceIds as $id) {
            $sync[$id] = self::defaultPivotRow();
        }
        $target->schedulingResources()->sync($sync);
    }

    /**
     * Новые связи получают {@see defaultPivotRow()}, для уже существующих сохраняются текущие pivot-поля.
     *
     * @param  list<int>  $orderedResourceIds
     */
    public static function syncPreservingExistingPivot(SchedulingTarget $target, array $orderedResourceIds): void
    {
        $existing = $target->schedulingResources()->get()->keyBy(fn ($r): int => (int) $r->id);
        $sync = [];
        foreach ($orderedResourceIds as $id) {
            $resource = $existing->get($id);
            if ($resource !== null) {
                $sync[$id] = [
                    'priority' => (int) $resource->pivot->priority,
                    'is_default' => (bool) $resource->pivot->is_default,
                    'assignment_strategy' => (string) $resource->pivot->assignment_strategy,
                ];
            } else {
                $sync[$id] = self::defaultPivotRow();
            }
        }
        $target->schedulingResources()->sync($sync);
    }
}
