<?php

namespace Tests\Unit\Terminology;

use App\Terminology\DomainTermEmergencyLabels;
use App\Terminology\DomainTermKeys;
use Tests\TestCase;

class DomainTermEmergencyLabelsTest extends TestCase
{
    public function test_ru_map_covers_every_domain_term_key(): void
    {
        $map = DomainTermEmergencyLabels::ruMap();
        foreach (DomainTermKeys::all() as $key) {
            $this->assertArrayHasKey(
                $key,
                $map,
                'Add Russian emergency label for '.$key.' in DomainTermEmergencyLabels::ruMap()'
            );
            $this->assertNotSame('', trim($map[$key]));
        }
        $this->assertCount(count(DomainTermKeys::all()), $map);
    }
}
