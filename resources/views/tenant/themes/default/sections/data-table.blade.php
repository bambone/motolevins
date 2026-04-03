@php
    $title = $data['title'] ?? '';
    $columns = is_array($data['columns'] ?? null) ? $data['columns'] : [];
    $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
    $headers = [];
    foreach ($columns as $col) {
        if (is_array($col) && isset($col['name'])) {
            $headers[] = (string) $col['name'];
        }
    }
    if ($headers === [] && $rows !== []) {
        $firstRow = $rows[0] ?? [];
        $cells = is_array($firstRow['cells'] ?? null) ? $firstRow['cells'] : [];
        $headers = array_map(fn ($i) => 'Колонка '.($i + 1), array_keys($cells));
    }
@endphp
<section class="w-full min-w-0" data-page-section-type="{{ $section->section_type }}">
    @if(filled($title))
        <h2 class="mb-4 text-balance text-xl font-semibold text-white sm:text-2xl">{{ $title }}</h2>
    @endif
    @if($headers !== [] || $rows !== [])
        <div class="-mx-1 overflow-x-auto sm:mx-0">
            <table class="w-full min-w-[280px] border-collapse text-left text-sm text-silver">
                @if($headers !== [])
                    <thead>
                        <tr class="border-b border-white/15">
                            @foreach($headers as $h)
                                <th scope="col" class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white/70 sm:px-4 sm:py-3">{{ $h }}</th>
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
                            @foreach($cells as $cell)
                                @php
                                    $val = is_array($cell) ? ($cell['value'] ?? '') : '';
                                @endphp
                                <td class="px-3 py-2 align-top sm:px-4 sm:py-3">{{ $val }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
