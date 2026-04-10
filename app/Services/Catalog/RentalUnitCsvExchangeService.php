<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\MotorcycleLocationMode;
use App\Models\Motorcycle;
use App\Models\RentalUnit;
use App\Models\TenantLocation;
use Illuminate\Support\Facades\DB;

final class RentalUnitCsvExchangeService
{
    /**
     * @return list<list<string>>
     */
    public function exportRowsForMotorcycle(Motorcycle $motorcycle): array
    {
        $mode = $motorcycle->location_mode ?? MotorcycleLocationMode::Everywhere;
        $rows = [];
        foreach ($motorcycle->rentalUnits()->orderBy('id')->get() as $unit) {
            $slugList = '';
            if ($mode === MotorcycleLocationMode::PerUnit) {
                $slugList = $unit->tenantLocations()->orderBy('slug')->pluck('slug')->implode('|');
            }
            $rows[] = [
                (string) $unit->id,
                (string) ($unit->unit_label ?? ''),
                (string) $unit->status,
                (string) ($unit->external_id ?? ''),
                $slugList,
                (string) ($unit->notes ?? ''),
            ];
        }

        return $rows;
    }

    public function exportToCsvString(Motorcycle $motorcycle): string
    {
        $fh = fopen('php://temp', 'r+');
        if ($fh === false) {
            return '';
        }
        fputcsv($fh, ['id', 'unit_label', 'status', 'external_id', 'location_slugs', 'notes']);
        foreach ($this->exportRowsForMotorcycle($motorcycle) as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);

        return $csv;
    }

    /**
     * @return array{created: int, updated: int, errors: list<string>}
     */
    public function importFromCsvString(Motorcycle $motorcycle, string $csvBody, bool $perUnitLocations): array
    {
        $tenantId = (int) $motorcycle->tenant_id;
        $motorcycleId = (int) $motorcycle->id;
        $created = 0;
        $updated = 0;
        $errors = [];

        $locationBySlug = TenantLocation::query()
            ->where('tenant_id', $tenantId)
            ->pluck('id', 'slug')
            ->all();

        $lines = preg_split('/\r\n|\r|\n/', $csvBody) ?: [];
        if ($lines === []) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['Пустой файл.']];
        }
        $header = str_getcsv(array_shift($lines) ?: '');
        $headerMap = [];
        foreach ($header as $i => $h) {
            $headerMap[trim((string) $h)] = $i;
        }
        $required = ['id', 'unit_label', 'status', 'external_id', 'location_slugs', 'notes'];
        foreach ($required as $col) {
            if (! array_key_exists($col, $headerMap)) {
                return ['created' => 0, 'updated' => 0, 'errors' => ["Нет колонки «{$col}» в заголовке CSV."]];
            }
        }

        $lineNo = 1;
        foreach ($lines as $line) {
            $lineNo++;
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cells = str_getcsv($line);
            $get = fn (string $k): string => isset($headerMap[$k], $cells[$headerMap[$k]])
                ? trim((string) $cells[$headerMap[$k]])
                : '';

            $idRaw = $get('id');
            $id = $idRaw !== '' ? (int) $idRaw : null;
            $unitLabel = $get('unit_label');
            $status = $get('status') !== '' ? $get('status') : 'active';
            $externalId = $get('external_id');
            $slugsRaw = $get('location_slugs');
            $notes = $get('notes');

            if (! in_array($status, array_keys(RentalUnit::statuses()), true)) {
                $errors[] = "Строка {$lineNo}: неизвестный статус «{$status}».";

                continue;
            }

            try {
                DB::beginTransaction();
                if ($id !== null) {
                    $unit = RentalUnit::query()
                        ->whereKey($id)
                        ->where('tenant_id', $tenantId)
                        ->where('motorcycle_id', $motorcycleId)
                        ->first();
                    if ($unit === null) {
                        DB::rollBack();
                        $errors[] = "Строка {$lineNo}: единица id={$id} не найдена для этой карточки.";

                        continue;
                    }
                    $unit->update([
                        'unit_label' => $unitLabel !== '' ? $unitLabel : null,
                        'status' => $status,
                        'external_id' => $externalId !== '' ? $externalId : null,
                        'notes' => $notes !== '' ? $notes : null,
                    ]);
                    if ($perUnitLocations) {
                        $this->syncSlugsToUnit($unit, $slugsRaw, $locationBySlug, $lineNo, $errors);
                    }
                    $updated++;
                } else {
                    if ($externalId !== '') {
                        $existing = RentalUnit::query()
                            ->where('tenant_id', $tenantId)
                            ->where('motorcycle_id', $motorcycleId)
                            ->where('external_id', $externalId)
                            ->first();
                        if ($existing !== null) {
                            $existing->update([
                                'unit_label' => $unitLabel !== '' ? $unitLabel : null,
                                'status' => $status,
                                'notes' => $notes !== '' ? $notes : null,
                            ]);
                            if ($perUnitLocations) {
                                $this->syncSlugsToUnit($existing, $slugsRaw, $locationBySlug, $lineNo, $errors);
                            }
                            $updated++;
                            DB::commit();

                            continue;
                        }
                    }
                    $unit = RentalUnit::query()->create([
                        'tenant_id' => $tenantId,
                        'motorcycle_id' => $motorcycleId,
                        'unit_label' => $unitLabel !== '' ? $unitLabel : null,
                        'status' => $status,
                        'external_id' => $externalId !== '' ? $externalId : null,
                        'notes' => $notes !== '' ? $notes : null,
                        'config' => null,
                    ]);
                    if ($perUnitLocations) {
                        $this->syncSlugsToUnit($unit, $slugsRaw, $locationBySlug, $lineNo, $errors);
                    }
                    $created++;
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors[] = "Строка {$lineNo}: ".$e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * @param  array<string, int>  $locationBySlug
     * @param  list<string>  $errors
     */
    private function syncSlugsToUnit(RentalUnit $unit, string $slugsRaw, array $locationBySlug, int $lineNo, array &$errors): void
    {
        if ($slugsRaw === '') {
            $unit->tenantLocations()->sync([]);

            return;
        }
        $parts = array_values(array_filter(array_map('trim', explode('|', $slugsRaw))));
        $ids = [];
        foreach ($parts as $slug) {
            if (! isset($locationBySlug[$slug])) {
                $errors[] = "Строка {$lineNo}: неизвестный slug локации «{$slug}».";

                continue;
            }
            $ids[] = $locationBySlug[$slug];
        }
        $unit->tenantLocations()->sync(array_values(array_unique($ids)));
    }
}
