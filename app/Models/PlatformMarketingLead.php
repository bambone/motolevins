<?php

namespace App\Models;

use App\Product\CRM\Actions\CreateCrmRequestFromPublicForm;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Переходный слой. Новые заявки с маркетингового сайта пишутся в {@see CrmRequest} через
 * {@see CreateCrmRequestFromPublicForm}. Таблица не используется для новых записей;
 * дальнейшая судьба — миграция данных / удаление по тикету.
 */
class PlatformMarketingLead extends Model
{
    protected $table = 'platform_marketing_leads';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'intent',
        'message',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];
}
