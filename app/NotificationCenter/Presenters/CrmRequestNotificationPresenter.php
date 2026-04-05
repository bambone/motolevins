<?php

namespace App\NotificationCenter\Presenters;

use App\Models\CrmRequest;
use App\Models\Tenant;
use App\NotificationCenter\NotificationActionUrlBuilder;
use App\NotificationCenter\NotificationPayloadDto;

final class CrmRequestNotificationPresenter
{
    public function __construct(
        private readonly NotificationActionUrlBuilder $urls,
    ) {}

    public function payloadForCreated(Tenant $tenant, CrmRequest $crm): NotificationPayloadDto
    {
        $actionUrl = $this->urls->urlForSubject($tenant, class_basename(CrmRequest::class), (int) $crm->id);
        $preview = mb_strlen($crm->message ?? '') > 160
            ? mb_substr((string) $crm->message, 0, 157).'…'
            : (string) ($crm->message ?? '');

        $body = trim($crm->name."\n".$crm->phone.($crm->email ? "\n".$crm->email : ''));
        if ($preview !== '') {
            $body .= "\n\n".$preview;
        }

        return new NotificationPayloadDto(
            title: 'Новая заявка',
            body: $body,
            actionUrl: $actionUrl,
            actionLabel: 'Открыть заявку',
            meta: [
                'source' => $crm->source,
                'channel' => $crm->channel,
                'request_type' => $crm->request_type,
            ],
        );
    }

    public function payloadForStatusChanged(Tenant $tenant, CrmRequest $crm, string $from, string $to): NotificationPayloadDto
    {
        $actionUrl = $this->urls->urlForSubject($tenant, class_basename(CrmRequest::class), (int) $crm->id);

        return new NotificationPayloadDto(
            title: 'Статус заявки изменён',
            body: $from.' → '.$to,
            actionUrl: $actionUrl,
            actionLabel: 'Открыть заявку',
            meta: ['from' => $from, 'to' => $to],
        );
    }

    public function payloadForNoteAdded(Tenant $tenant, CrmRequest $crm, string $preview): NotificationPayloadDto
    {
        $actionUrl = $this->urls->urlForSubject($tenant, class_basename(CrmRequest::class), (int) $crm->id);

        return new NotificationPayloadDto(
            title: 'Комментарий к заявке',
            body: $preview,
            actionUrl: $actionUrl,
            actionLabel: 'Открыть заявку',
            meta: [],
        );
    }

    public function payloadForFirstViewed(Tenant $tenant, CrmRequest $crm): NotificationPayloadDto
    {
        $actionUrl = $this->urls->urlForSubject($tenant, class_basename(CrmRequest::class), (int) $crm->id);

        return new NotificationPayloadDto(
            title: 'Заявка просмотрена',
            body: $crm->name,
            actionUrl: $actionUrl,
            actionLabel: 'Открыть заявку',
            meta: [],
        );
    }

    public function payloadForUnviewed5m(Tenant $tenant, CrmRequest $crm): NotificationPayloadDto
    {
        $actionUrl = $this->urls->urlForSubject($tenant, class_basename(CrmRequest::class), (int) $crm->id);

        return new NotificationPayloadDto(
            title: 'Заявка не просмотрена (5 мин)',
            body: $crm->name."\n".$crm->phone,
            actionUrl: $actionUrl,
            actionLabel: 'Открыть заявку',
            meta: ['sla' => 'unviewed_5m'],
        );
    }

    public function payloadForUnprocessed15m(Tenant $tenant, CrmRequest $crm): NotificationPayloadDto
    {
        $actionUrl = $this->urls->urlForSubject($tenant, class_basename(CrmRequest::class), (int) $crm->id);

        return new NotificationPayloadDto(
            title: 'Заявка без обработки (15 мин)',
            body: $crm->name."\n".$crm->phone,
            actionUrl: $actionUrl,
            actionLabel: 'Открыть заявку',
            meta: ['sla' => 'unprocessed_15m'],
        );
    }
}
