<?php

namespace App\PageBuilder;

use App\PageBuilder\Contracts\PageSectionBlueprintInterface;

/**
 * Immutable descriptor for one image framing slot in a section blueprint (metadata; not persisted in section JSON).
 *
 * @see PageSectionBlueprintInterface
 */
final class BlueprintFramingSlotDescriptor
{
    /**
     * @param  'modal'|'inline'  $editorMode
     */
    public function __construct(
        public readonly string $slotKey,
        public readonly string $imageField,
        public readonly string $presentationField,
        public readonly string $profileSlotId,
        public readonly string $editorMode = 'modal',
    ) {}

    /**
     * @param  list<array{slot_key: string, image_field: string, presentation_field: string, profile_slot_id: string, editor_mode?: string}>  $rows
     * @return list<self>
     */
    public static function listFrom(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = new self(
                slotKey: (string) ($row['slot_key'] ?? ''),
                imageField: (string) ($row['image_field'] ?? ''),
                presentationField: (string) ($row['presentation_field'] ?? ''),
                profileSlotId: (string) ($row['profile_slot_id'] ?? ''),
                editorMode: (string) ($row['editor_mode'] ?? 'modal'),
            );
        }

        return $out;
    }
}
