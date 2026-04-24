<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Review;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ReviewPublicReviewDateFormattedTest extends TestCase
{
    #[Test]
    public function uses_published_date_when_filled(): void
    {
        $review = Review::make([
            'date' => '2024-09-12',
            'submitted_at' => '2024-01-01 10:00:00',
        ]);

        $this->assertSame('12.09.2024', $review->publicReviewDateFormatted());
    }

    #[Test]
    public function falls_back_to_submitted_at_when_date_not_filled(): void
    {
        $review = Review::make([
            'date' => null,
            'submitted_at' => '2024-10-04 15:00:00',
        ]);

        $this->assertSame('04.10.2024', $review->publicReviewDateFormatted());
    }

    #[Test]
    public function falls_back_to_submitted_at_when_string_date_is_unparseable(): void
    {
        $review = new Review;
        $review->setRawAttributes([
            'date' => 'not-a-date',
            'submitted_at' => '2020-11-18 00:00:00',
        ]);

        $this->assertSame('18.11.2020', $review->publicReviewDateFormatted());
    }

    #[Test]
    public function accepts_carbon_submitted_at(): void
    {
        $review = Review::make([
            'date' => null,
            'submitted_at' => Carbon::parse('2024-03-01 12:00:00'),
        ]);

        $this->assertSame('01.03.2024', $review->publicReviewDateFormatted());
    }

    #[Test]
    public function returns_empty_when_both_unusable(): void
    {
        $review = new Review;
        $review->setRawAttributes([
            'date' => 'nope',
            'submitted_at' => 'also-nope',
        ]);

        $this->assertSame('', $review->publicReviewDateFormatted());
    }
}
