<?php

namespace App\NotificationCenter;

enum NotificationDestinationStatus: string
{
    case Draft = 'draft';
    case PendingVerification = 'pending_verification';
    case Verified = 'verified';
    case Disabled = 'disabled';
    case Failed = 'failed';
}
