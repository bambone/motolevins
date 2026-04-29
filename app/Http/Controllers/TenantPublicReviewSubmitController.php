<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantPublicReviewRequest;
use App\Models\Review;
use App\Tenant\Reviews\TenantReviewSubmitConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

final class TenantPublicReviewSubmitController extends Controller
{
    public function store(StoreTenantPublicReviewRequest $request): JsonResponse
    {
        $tenant = currentTenant();
        if ($tenant === null) {
            abort(404);
        }

        $tid = (int) $tenant->id;
        $cfg = TenantReviewSubmitConfig::forTenant($tid);
        if (! $cfg->publicSubmitEnabled) {
            return response()->json(['message' => 'Отправка отзывов с сайта отключена.'], 403);
        }

        $honeypot = trim((string) $request->input('website', ''));
        if ($honeypot !== '') {
            Log::warning('tenant_review_submit_honeypot', [
                'tenant_id' => $tid,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $cfg->moderationEnabled ? $cfg->successMessagePending : $cfg->successMessagePublished,
            ]);
        }

        $rateKey = 'tenant-review-submit:'.$tid.':'.($request->ip() ?? '0');
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            Log::notice('tenant_review_submit_rate_limited', ['tenant_id' => $tid, 'ip' => $request->ip()]);

            return response()->json([
                'message' => 'Слишком много отправок. Подождите минуту и попробуйте снова.',
            ], 429);
        }
        RateLimiter::hit($rateKey, 60);

        $validated = $request->validated();
        $body = trim((string) $validated['body']);
        $name = trim((string) $validated['name']);
        $city = isset($validated['city']) ? trim((string) $validated['city']) : '';
        $city = $city === '' ? null : $city;
        $contactEmail = isset($validated['contact_email']) ? trim((string) $validated['contact_email']) : '';
        $contactEmail = $contactEmail === '' ? null : $contactEmail;

        $rating = null;
        $rawRating = $validated['rating'] ?? null;
        if ($cfg->showRatingField && $rawRating !== null && $rawRating !== '') {
            $rating = max(1, min(5, (int) $rawRating));
        }

        $status = $cfg->moderationEnabled ? 'pending' : 'published';
        $now = now();

        $meta = [
            'source_type' => 'frontend_review_submit',
            'source_path' => parse_url((string) $request->input('page_url', ''), PHP_URL_PATH) ?: '/otzyvy',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        $review = new Review;
        $review->tenant_id = $tid;
        $review->name = $name;
        $review->city = $city;
        $review->contact_email = $contactEmail;
        $review->body = $body;
        $review->rating = $rating;
        $review->status = $status;
        $review->is_featured = false;
        $review->sort_order = 9999;
        $review->source = 'site';
        $review->date = $now->toDateString();
        $review->submitted_at = $now;
        $review->meta_json = array_merge($review->meta_json ?? [], $meta);
        $review->media_type = 'text';
        $review->save();

        $message = $status === 'pending' ? $cfg->successMessagePending : $cfg->successMessagePublished;

        return response()->json([
            'success' => true,
            'message' => $message,
            'status' => $status,
        ]);
    }
}
