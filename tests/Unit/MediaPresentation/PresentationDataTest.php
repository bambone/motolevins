<?php

namespace Tests\Unit\MediaPresentation;

use App\MediaPresentation\PresentationData;
use Tests\TestCase;

final class PresentationDataTest extends TestCase
{
    public function test_viewport_focal_map_drops_unknown_keys(): void
    {
        $d = PresentationData::fromArray([
            'version' => 1,
            'viewport_focal_map' => [
                'mobile' => ['x' => 40.0, 'y' => 60.0],
                'moblie' => ['x' => 10.0, 'y' => 20.0],
                'desktop' => ['x' => 55.0, 'y' => 45.0],
            ],
        ]);

        $this->assertArrayHasKey('mobile', $d->viewportFocalMap);
        $this->assertArrayHasKey('desktop', $d->viewportFocalMap);
        $this->assertArrayNotHasKey('moblie', $d->viewportFocalMap);
    }

    public function test_from_array_unwraps_single_element_list_payload(): void
    {
        $d = PresentationData::fromArray([
            [
                'version' => 1,
                'viewport_focal_map' => [
                    'mobile' => ['x' => 50.0, 'y' => 80.0],
                    'desktop' => ['x' => 50.0, 'y' => 20.0],
                ],
            ],
        ]);

        $this->assertSame(1, $d->version);
        $this->assertSame(80.0, $d->viewportFocalMap['mobile']['y']);
        $this->assertSame(20.0, $d->viewportFocalMap['desktop']['y']);
    }
}
