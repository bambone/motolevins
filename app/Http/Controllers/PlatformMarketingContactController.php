<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlatformMarketingContactRequest;
use App\Mail\PlatformMarketingContactMail;
use App\Models\PlatformMarketingLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

class PlatformMarketingContactController extends Controller
{
    public function store(PlatformMarketingContactRequest $request): RedirectResponse
    {
        $intent = (string) ($request->validated('intent') ?? '');
        if ($intent === '') {
            $intent = (string) (config('platform_marketing.intent.launch') ?? 'launch');
        }

        $intentsMeta = config('platform_marketing.contact_page.intents', []);
        $intentMeta = is_array($intentsMeta[$intent] ?? null) ? $intentsMeta[$intent] : [];
        $intentLabel = (string) ($intentMeta['title'] ?? $intent);

        $payload = [
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'message' => $request->validated('message'),
            'intent' => $intent,
            'intent_label' => $intentLabel,
            'utm_source' => $request->validated('utm_source'),
            'utm_medium' => $request->validated('utm_medium'),
            'utm_campaign' => $request->validated('utm_campaign'),
            'utm_content' => $request->validated('utm_content'),
            'utm_term' => $request->validated('utm_term'),
            'page_url' => $request->headers->get('referer'),
            'ip' => $request->ip(),
        ];

        $to = trim((string) config('platform_marketing.contact_mail_to', ''));
        if ($to === '') {
            $to = (string) config('mail.from.address', '');
        }
        if ($to !== '') {
            Mail::to($to)->send(new PlatformMarketingContactMail($payload));
        } else {
            Log::warning('platform_marketing.contact: no mail recipient (contact_mail_to / mail.from.address empty).');
        }

        try {
            PlatformMarketingLead::query()->create([
                'name' => $payload['name'],
                'phone' => $payload['phone'],
                'email' => $payload['email'],
                'intent' => $payload['intent'],
                'message' => $payload['message'],
                'utm_source' => $payload['utm_source'],
                'utm_medium' => $payload['utm_medium'],
                'utm_campaign' => $payload['utm_campaign'],
                'utm_content' => $payload['utm_content'],
                'utm_term' => $payload['utm_term'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('platform_marketing.lead_store: '.$e->getMessage(), ['exception' => $e]);
        }

        $q = platform_marketing_tracking_query();
        if (Route::has('platform.contact')) {
            $target = route('platform.contact', $q);
        } else {
            $target = url('/contact');
            if ($q !== []) {
                $target .= (str_contains($target, '?') ? '&' : '?').http_build_query($q);
            }
        }

        return redirect()
            ->to($target)
            ->with('platform_contact_sent', true)
            ->with('platform_contact_intent', $intent);
    }
}
