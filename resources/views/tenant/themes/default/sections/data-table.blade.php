@php
    $title = $data['title'] ?? '';
    $columns = is_array($data['columns'] ?? null) ? $data['columns'] : [];
    $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
    /** @var list<array{h: string, k: string}> $columnPairs */
    $columnPairs = [];
    foreach ($columns as $col) {
        if (is_array($col) && isset($col['name'])) {
            $columnPairs[] = [
                'h' => (string) $col['name'],
                'k' => (string) ($col['key'] ?? ''),
            ];
        }
    }
    if ($columnPairs === [] && $rows !== []) {
        $firstRow = $rows[0] ?? [];
        $cells = is_array($firstRow['cells'] ?? null) ? $firstRow['cells'] : [];
        if ($cells !== [] && array_is_list($cells)) {
            foreach ($cells as $i => $_) {
                $columnPairs[] = ['h' => 'Колонка '.($i + 1), 'k' => ''];
            }
        } elseif ($cells !== [] && ! array_is_list($cells)) {
            $i = 0;
            foreach (array_keys($cells) as $cellKey) {
                $i++;
                $columnPairs[] = [
                    'h' => 'Колонка '.$i,
                    'k' => (string) $cellKey,
                ];
            }
        }
    }

    $cellDisplay = static function (array $cells, string $key, int $index): string {
        if ($key !== '' && array_key_exists($key, $cells)) {
            $raw = $cells[$key];

            return is_array($raw) ? trim((string) ($raw['value'] ?? '')) : trim((string) $raw);
        }
        if (array_is_list($cells) && array_key_exists($index, $cells)) {
            $raw = $cells[$index];

            return is_array($raw) ? trim((string) ($raw['value'] ?? '')) : trim((string) $raw);
        }

        return '';
    };
@endphp
<section class="w-full min-w-0" data-page-section-type="{{ $section->section_type }}">
    @if(filled($title))
        <h2 class="mb-4 text-balance text-xl font-semibold text-white sm:text-2xl">{{ $title }}</h2>
    @endif
    @if($columnPairs !== [] || $rows !== [])
        <div class="-mx-1 overflow-x-auto sm:mx-0">
            <table class="w-full min-w-[280px] border-collapse text-left text-sm text-silver">
                @if($columnPairs !== [])
                    <thead>
                        <tr class="border-b border-white/15">
                            @foreach($columnPairs as $pair)
                                <th scope="col" class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white/70 sm:px-4 sm:py-3">{{ $pair['h'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $cells = is_array($row) && is_array($row['cells'] ?? null) ? $row['cells'] : [];
                        @endphp
                        <tr class="border-b border-white/10 odd:bg-white/[0.02]">
                            @foreach($columnPairs as $idx => $pair)
                                <td class="px-3 py-2 align-top sm:px-4 sm:py-3">{{ $cellDisplay($cells, $pair['k'], $idx) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
