<?php

namespace App\NotificationCenter;

enum NotificationDeliveryMode: string
{
    case Immediate = 'immediate';
    case Digest = 'digest';
    case Fallback = 'fallback';
    case Escalation = 'escalation';
}
