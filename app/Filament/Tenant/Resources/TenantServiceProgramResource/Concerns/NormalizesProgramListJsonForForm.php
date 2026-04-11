<?php

namespace App\Filament\Tenant\Resources\TenantServiceProgramResource\Concerns;

/**
 * Конвертация audience_json / outcomes_json между форматом БД (список строк)
 * и состоянием Repeater в форме ([['text' => '…'], …]).
 */
trait NormalizesProgramListJsonForForm
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeProgramJsonListsForForm(array $data): array
    {
        foreach (['audience_json', 'outcomes_json'] as $key) {
            $data[$key] = $this->jsonLinesToRepeaterState($data[$key] ?? null);
        }

        return $this->normalizeCoverObjectPositionForFormFill($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeProgramJsonListsForSave(array $data): array
    {
        foreach (['audience_json', 'outcomes_json'] as $key) {
            $data[$key] = $this->repeaterStateToJsonLines($data[$key] ?? null);
        }

        return $this->normalizeCoverObjectPositionForSave($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeCoverObjectPositionForFormFill(array $data): array
    {
        $raw = trim((string) ($data['cover_object_position'] ?? ''));
        $known = ['center top', 'center 22%', 'center 30%', 'center center', 'center 72%', 'center bottom'];
        if ($raw === '') {
            $data['cover_object_position_preset'] = 'auto';
        } elseif (in_array($raw, $known, true)) {
            $data['cover_object_position_preset'] = $raw;
        } else {
            $data['cover_object_position_preset'] = '__other__';
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeCoverObjectPositionForSave(array $data): array
    {
        if (! array_key_exists('cover_object_position_preset', $data)) {
            return $data;
        }

        $preset = $data['cover_object_position_preset'];
        unset($data['cover_object_position_preset']);

        if ($preset === '__other__') {
            $custom = trim((string) ($data['cover_object_position'] ?? ''));
            $data['cover_object_position'] = $custom === '' ? null : $custom;
        } elseif ($preset === 'auto' || $preset === '' || $preset === null) {
            $data['cover_object_position'] = null;
        } else {
            $data['cover_object_position'] = (string) $preset;
        }

        return $data;
    }

    /**
     * @return list<array{text: string}>
     */
    private function jsonLinesToRepeaterState(mixed $json): array
    {
        if (! is_array($json)) {
            return [];
        }
        $out = [];
        foreach ($json as $item) {
            if (is_string($item)) {
                $t = trim($item);
                if ($t !== '') {
                    $out[] = ['text' => $t];
                }
            } elseif (is_array($item) && filled($item['text'] ?? null)) {
                $t = trim((string) $item['text']);
                if ($t !== '') {
                    $out[] = ['text' => $t];
                }
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function repeaterStateToJsonLines(mixed $state): array
    {
        if (! is_array($state)) {
            return [];
        }
        $lines = [];
        foreach ($state as $row) {
            if (! is_array($row)) {
                continue;
            }
            $t = trim((string) ($row['text'] ?? ''));
            if ($t !== '') {
                $lines[] = $t;
            }
        }

        return $lines;
    }
}
