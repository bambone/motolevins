<?php

namespace App\ContactChannels;

/**
 * Одна запись канала в contact_channels.state (мапа по ключу типа).
 */
final class TenantContactChannelConfig
{
    public function __construct(
        public bool $usesChannel = false,
        public bool $publicVisible = false,
        public bool $allowedInForms = false,
        public string $businessValue = '',
        public int $sortOrder = 99,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            usesChannel: ! empty($row['uses_channel']),
            publicVisible: ! empty($row['public_visible']),
            allowedInForms: ! empty($row['allowed_in_forms']),
            businessValue: isset($row['business_value']) ? (string) $row['business_value'] : '',
            sortOrder: isset($row['sort_order']) ? (int) $row['sort_order'] : 99,
        );
    }

    /**
     * @return array{uses_channel: bool, public_visible: bool, allowed_in_forms: bool, business_value: string, sort_order: int}
     */
    public function toArray(): array
    {
        return [
            'uses_channel' => $this->usesChannel,
            'public_visible' => $this->publicVisible,
            'allowed_in_forms' => $this->allowedInForms,
            'business_value' => $this->businessValue,
            'sort_order' => $this->sortOrder,
        ];
    }
}
