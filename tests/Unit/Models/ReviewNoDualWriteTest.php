<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\TestCase;

final class ReviewNoDualWriteTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use RefreshDatabase;

    public function test_saving_review_with_only_body_does_not_auto_fill_legacy_text_columns(): void
    {
        $tenant = $this->createTenantWithActiveDomain('rev-nodual');
        $review = Review::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Author',
            'body' => 'Только канонический текст.',
            'rating' => 5,
            'status' => 'draft',
            'sort_order' => 0,
        ]);

        $this->assertSame('Только канонический текст.', $review->publicFullTextRaw());

        $fresh = Review::query()->withoutGlobalScopes()->findOrFail($review->id);
        $this->assertNull($fresh->text);
        $this->assertNull($fresh->text_short);
        $this->assertNull($fresh->text_long);
    }
}
