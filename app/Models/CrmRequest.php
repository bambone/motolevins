<?php

namespace App\Models;

use App\Product\CRM\Actions\CreateCrmRequestFromPublicForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Aggregate root inbound CRM: notes, activity/timeline, mail hooks, assignment и state machine статуса
 * относятся к этой сущности, а не к Lead / platform_marketing_leads / Booking как к параллельному SoT.
 *
 * @see CreateCrmRequestFromPublicForm
 */
class CrmRequest extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_LOST = 'lost';

    public const STATUS_SPAM = 'spam';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'message',
        'request_type',
        'source',
        'channel',
        'pipeline',
        'status',
        'assigned_user_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'referrer',
        'landing_page',
        'ip',
        'user_agent',
        'payload_json',
        'last_activity_at',
        'closed_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'last_activity_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmRequestActivity::class)->orderByDesc('created_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CrmRequestNote::class)->orderByDesc('created_at');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_NEW => 'Новая',
            self::STATUS_IN_REVIEW => 'На рассмотрении',
            self::STATUS_CONTACTED => 'Связались',
            self::STATUS_QUALIFIED => 'Квалифицирована',
            self::STATUS_CONVERTED => 'Конверсия',
            self::STATUS_LOST => 'Потеряна',
            self::STATUS_SPAM => 'Спам',
            self::STATUS_ARCHIVED => 'Архив',
        ];
    }
}
