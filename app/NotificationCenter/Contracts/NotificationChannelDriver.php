<?php

namespace App\NotificationCenter\Contracts;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;

interface NotificationChannelDriver
{
    public function send(
        NotificationDelivery $delivery,
        NotificationEvent $event,
        NotificationDestination $destination,
    ): void;
}
