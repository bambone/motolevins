<?php

namespace App\PageBuilder;

use App\Livewire\Tenant\PageSectionsBuilder;

/**
 * Canonical Livewire paths for PageSectionsBuilder {@code sectionFormData} (Filament state path {@code data_json.*}).
 *
 * **Policy:** nested paths for {@code PresentationData} under section JSON must be built only through this helper
 * (or {@see self::presentationViewportMap}) so Livewire wire.set targets stay in sync with
 * {@see PageSectionsBuilder::$sectionFormData} and {@code resources/js/service-program-cover-focal-editor.js}
 * ({@code wirePathPrefix} + {@code .mobile.x|y|scale} …).
 *
 * Root path: {@code sectionFormData} (Livewire public property on {@see PageSectionsBuilder}).
 */
final class PageSectionFormWirePath
{
    public const SECTION_FORM_ROOT = 'sectionFormData';

    public const DATA_JSON_PREFIX = 'sectionFormData.data_json';

    /**
     * Path to {@code PresentationData::viewportFocal_map} for a top-level field in {@code data_json}.
     */
    public static function presentationViewportMap(string $presentationField): string
    {
        return self::DATA_JSON_PREFIX.'.'.trim($presentationField, '.').'.viewport_focal_map';
    }

    /**
     * Prefix for the focal editor JS ({@code wirePathPrefix}): full path to viewport map without trailing segment.
     */
    public static function presentationWirePathPrefix(string $presentationField): string
    {
        return self::presentationViewportMap($presentationField);
    }

    /**
     * Repeater item: path to viewport map for {@code presentationField} nested under {@code data_json.items.<itemKey>}.
     * {@code itemKey} is the Filament repeater UUID string.
     */
    public static function repeaterItemPresentationViewportMap(string $presentationField, string $itemKey): string
    {
        $key = trim($itemKey);

        return self::DATA_JSON_PREFIX.'.items.'.$key.'.'.trim($presentationField, '.').'.viewport_focal_map';
    }

    /**
     * Prefix for focal editor inside a repeater item.
     */
    public static function repeaterItemPresentationWirePathPrefix(string $presentationField, string $itemKey): string
    {
        return self::repeaterItemPresentationViewportMap($presentationField, $itemKey);
    }
}
