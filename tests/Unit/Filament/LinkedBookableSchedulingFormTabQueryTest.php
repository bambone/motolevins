<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Tenant\Forms\LinkedBookableSchedulingForm;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class LinkedBookableSchedulingFormTabQueryTest extends TestCase
{
    public function test_reads_tab_from_referer_when_query_empty(): void
    {
        $request = Request::create('/livewire/update', 'POST', [], [], [], [
            'HTTP_REFERER' => 'https://motolevins.rentbase.local/admin/motorcycles/1/edit?moto_edit_tab='.rawurlencode('onlajn-zapis::data::tab'),
        ]);

        $tab = LinkedBookableSchedulingForm::resolveMotorcycleEditTabQueryFromRequest($request);

        $this->assertStringContainsString('onlajn-zapis', $tab);
    }

    public function test_prefers_query_over_referer(): void
    {
        $request = Request::create(
            '/livewire/update?moto_edit_tab=online-booking',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_REFERER' => 'https://example.test/admin/motorcycles/1/edit?moto_edit_tab='.rawurlencode('main::data::tab'),
            ],
        );

        $tab = LinkedBookableSchedulingForm::resolveMotorcycleEditTabQueryFromRequest($request);

        $this->assertSame('online-booking', $tab);
    }
}
