<?php

declare(strict_types=1);

namespace App\BookingConsent;

use App\Models\Tenant;
use App\Models\TenantBookingConsentItem;

/**
 * Формирует {@code legal_acceptances_json} в каноническом формате schema_version + items.
 */
final class BookingConsentSnapshotFactory
{
    public function __construct(
        private readonly TenantBookingConsentQuery $query,
    ) {}

    /**
     * @param  list<int>  $acceptedConsentItemIds  id пунктов, отмеченных пользователем
     * @return array<string, mixed>
     */
    public function build(Tenant $tenant, array $acceptedConsentItemIds, string $source): array
    {
        $accepted = [];
        foreach ($acceptedConsentItemIds as $id) {
            $accepted[(int) $id] = true;
        }

        $items = [];
        foreach ($this->query->enabledOrdered((int) $tenant->id) as $row) {
            $items[] = $this->itemPayload($row, ! empty($accepted[(int) $row->id]), $source);
        }

        return [
            'schema_version' => 1,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildFromRequest(Tenant $tenant, \Illuminate\Http\Request $request, string $source): array
    {
        $raw = $request->input('consent_accepted', []);
        $acceptedIds = [];
        if (is_array($raw)) {
            foreach ($raw as $k => $v) {
                if ($v === true || $v === 1 || $v === '1' || $v === 'on') {
                    $acceptedIds[] = (int) $k;
                }
            }
        }

        return $this->build($tenant, $acceptedIds, $source);
    }

    /**
     * Чтение старых снимков (массив без schema_version) — только для отображения истории.
     *
     * @param  array<string, mixed>|list<mixed>|null  $json
     */
    public static function isNewSchema(?array $json): bool
    {
        if ($json === null || $json === []) {
            return false;
        }

        return isset($json['schema_version']) && isset($json['items']) && is_array($json['items']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function itemPayload(TenantBookingConsentItem $item, bool $accepted, string $source): array
    {
        return [
            'consent_item_id' => (int) $item->id,
            'label' => (string) $item->label,
            'link_text' => $item->link_text !== null ? (string) $item->link_text : null,
            'link_url' => $item->link_url !== null ? (string) $item->link_url : null,
            'is_required' => (bool) $item->is_required,
            'accepted' => $accepted,
            'accepted_at' => $accepted ? now()->toIso8601String() : null,
            'source' => $source,
        ];
    }
}
