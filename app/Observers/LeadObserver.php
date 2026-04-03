<?php

namespace App\Observers;

use App\Models\CrmRequest;
use App\Models\CrmRequestActivity;
use App\Models\Lead;
use App\Models\LeadActivityLog;
use App\Models\LeadStatusHistory;
use App\Product\CRM\TenantBookingFromCrmConverter;
use Illuminate\Support\Facades\Auth;

/**
 * Мост legacy → CRM для статуса Lead.
 *
 * Политика (ADR-007):
 * - Источник истины по операторскому inbound-timeline — {@see CrmRequestActivity} у связанного {@see CrmRequest}.
 * - {@see LeadStatusHistory} — transitional projection / совместимость (отчёты, возможный legacy UI), не отдельный workflow engine.
 * - Новые экраны и продуктовая логика не должны опираться на LeadStatusHistory как на primary timeline.
 *
 * При смене status у Lead с заполненным crm_request_id дублируем событие в CRM activity с meta source=lead_status_projection.
 */
class LeadObserver
{
    public function created(Lead $lead): void
    {
        LeadStatusHistory::query()->create([
            'lead_id' => $lead->id,
            'old_status' => null,
            'new_status' => $lead->status,
            'changed_by' => Auth::id(),
        ]);

        LeadActivityLog::query()->create([
            'lead_id' => $lead->id,
            'actor_id' => Auth::id(),
            'type' => 'status_change',
            'payload' => [
                'old_status' => null,
                'new_status' => $lead->status,
                'source' => 'system',
            ],
            'comment' => 'Заявка создана',
        ]);

        if ($lead->status === 'confirmed') {
            app(TenantBookingFromCrmConverter::class)->materializeConfirmedLeadBooking($lead);
        }
    }

    public function updated(Lead $lead): void
    {
        if ($lead->wasChanged('status')) {
            LeadStatusHistory::query()->create([
                'lead_id' => $lead->id,
                'old_status' => $lead->getOriginal('status'),
                'new_status' => $lead->status,
                'changed_by' => Auth::id(),
            ]);

            LeadActivityLog::query()->create([
                'lead_id' => $lead->id,
                'actor_id' => Auth::id(),
                'type' => 'status_change',
                'payload' => [
                    'old_status' => $lead->getOriginal('status'),
                    'new_status' => $lead->status,
                    'source' => Auth::check() ? 'manager' : 'system',
                ],
            ]);

            if ($lead->crm_request_id !== null) {
                CrmRequestActivity::query()->create([
                    'crm_request_id' => $lead->crm_request_id,
                    'type' => CrmRequestActivity::TYPE_STATUS_CHANGED,
                    'meta' => [
                        'source' => 'lead_status_projection',
                        'lead_id' => $lead->id,
                        'old' => $lead->getOriginal('status'),
                        'new' => $lead->status,
                    ],
                    'actor_user_id' => Auth::id(),
                ]);
            }
        }

        $shouldTryBooking = $lead->status === 'confirmed'
            && (
                $lead->wasChanged('status')
                || $lead->wasChanged('motorcycle_id')
                || $lead->wasChanged('rental_date_from')
                || $lead->wasChanged('rental_date_to')
            );

        if ($shouldTryBooking) {
            app(TenantBookingFromCrmConverter::class)->materializeConfirmedLeadBooking($lead->fresh());
        }
    }
}
