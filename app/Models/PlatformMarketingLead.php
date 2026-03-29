<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
