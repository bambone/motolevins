<?php

namespace App\Contracts\Mail;

/**
 * Optional contract for Mailable classes to classify mail for analytics and limits.
 */
interface DefinesTenantMailMetadata
{
    public function tenantMailType(): string;

    public function tenantMailGroup(): string;
}
