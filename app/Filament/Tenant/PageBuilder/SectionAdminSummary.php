<?php

namespace App\Filament\Tenant\PageBuilder;

use App\Models\PageSection;

/**
 * Admin-only summary for page section cards, delete confirm, and similar UI.
 *
 * @phpstan-type ChannelHint array{icon: string, label: string, on: bool}
 * @phpstan-type SummaryArray array{
 *     displayTitle: string,
 *     displaySubtitle: string,
 *     summaryLines: list<string>,
 *     badges: list<string>,
 *     meta: array<string, string>,
 *     isEmpty: bool,
 *     warning: ?string,
 *     primaryHeadline: ?string,
 *     channels: list<ChannelHint>,
 *     onSiteLine: string,
 *     builderNotes: list<string>,
 *     presentationMeta: array<string, string>
 * }
 */
final readonly class SectionAdminSummary
{
    /**
     * @param  list<string>  $summaryLines
     * @param  list<string>  $badges
     * @param  array<string, string>  $meta
     * @param  list<ChannelHint>  $channels
     * @param  list<string>  $builderNotes
     * @param  array<string, string>  $presentationMeta render_mode, width_mode, page_mode, theme_key — для согласованности с публичным выводом
     */
    public function __construct(
        public string $displayTitle,
        public string $displaySubtitle,
        public array $summaryLines,
        public array $badges,
        public array $meta,
        public bool $isEmpty,
        public ?string $warning,
        public ?string $primaryHeadline = null,
        public array $channels = [],
        public string $onSiteLine = '',
        public array $builderNotes = [],
        public array $presentationMeta = [],
    ) {}

    /**
     * @return SummaryArray
     */
    public function toArray(): array
    {
        return [
            'displayTitle' => $this->displayTitle,
            'displaySubtitle' => $this->displaySubtitle,
            'summaryLines' => array_values($this->summaryLines),
            'badges' => array_values($this->badges),
            'meta' => $this->meta,
            'isEmpty' => $this->isEmpty,
            'warning' => $this->warning,
            'primaryHeadline' => $this->primaryHeadline,
            'channels' => array_values($this->channels),
            'onSiteLine' => $this->onSiteLine,
            'builderNotes' => array_values($this->builderNotes),
            'presentationMeta' => $this->presentationMeta,
        ];
    }

    /**
     * @param  list<string>  $extraNotes
     * @param  array<string, string>  $extraPresentation
     */
    public function withPublicLayer(string $onSiteLine, array $extraNotes = [], array $extraPresentation = []): self
    {
        $line = trim($onSiteLine) !== '' ? trim($onSiteLine) : $this->onSiteLine;

        return new self(
            displayTitle: $this->displayTitle,
            displaySubtitle: $this->displaySubtitle,
            summaryLines: $this->summaryLines,
            badges: $this->badges,
            meta: $this->meta,
            isEmpty: $this->isEmpty,
            warning: $this->warning,
            primaryHeadline: $this->primaryHeadline,
            channels: $this->channels,
            onSiteLine: $line,
            builderNotes: array_values(array_merge($this->builderNotes, $extraNotes)),
            presentationMeta: array_merge($this->presentationMeta, $extraPresentation),
        );
    }

    public function searchBlob(string $typeLabel): string
    {
        $parts = [
            $this->displayTitle,
            $this->displaySubtitle,
            $this->primaryHeadline ?? '',
            $typeLabel,
            $this->onSiteLine,
            ...$this->summaryLines,
            ...$this->badges,
            ...$this->builderNotes,
            ...array_column($this->channels, 'label'),
        ];

        return mb_strtolower(implode(' ', array_filter($parts)));
    }

    public static function fallbackUnknown(PageSection $section, string $typeId): self
    {
        $data = is_array($section->data_json) ? $section->data_json : [];
        $preview = SectionAdminPlainText::line((string) json_encode($data));
        if (strlen($preview) > 120) {
            $preview = substr($preview, 0, 120).'…';
        }
        $title = trim((string) ($section->title ?? ''));

        return new self(
            displayTitle: $title !== '' ? $title : $typeId,
            displaySubtitle: ($section->section_key ?? '').' · '.$typeId,
            summaryLines: $preview !== '' ? [$preview] : [],
            badges: [],
            meta: [],
            isEmpty: $preview === '' && $title === '',
            warning: 'Тип секции без детального превью',
            primaryHeadline: null,
            channels: [],
            onSiteLine: '',
            builderNotes: [],
            presentationMeta: ['render_mode' => 'unknown'],
        );
    }
}
