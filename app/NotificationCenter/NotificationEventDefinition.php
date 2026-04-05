<?php

namespace App\NotificationCenter;

/**
 * Metadata for a single event_key (PHP registry source of truth).
 */
final readonly class NotificationEventDefinition
{
    /**
     * @param  class-string|null  $templateClass
     */
    public function __construct(
        public string $key,
        public string $subjectType,
        public NotificationSeverity $defaultSeverity,
        public bool $supportsDigest,
        public bool $supportsRealtime,
        public string $defaultTitle,
        public ?string $templateClass,
        public string $category,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'subject_type' => $this->subjectType,
            'default_severity' => $this->defaultSeverity->value,
            'supports_digest' => $this->supportsDigest,
            'supports_realtime' => $this->supportsRealtime,
            'default_title' => $this->defaultTitle,
            'template_class' => $this->templateClass,
            'category' => $this->category,
        ];
    }
}
