<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum MatchConfidence: string
{
    case Exact = 'exact';

    case High = 'high';

    case Medium = 'medium';

    case ManualReviewRequired = 'manual_review_required';
}
