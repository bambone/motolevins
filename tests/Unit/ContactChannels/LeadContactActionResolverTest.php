<?php

namespace Tests\Unit\ContactChannels;

use App\ContactChannels\ContactChannelType;
use App\ContactChannels\LeadContactActionResolver;
use App\ContactChannels\TenantContactChannelConfig;
use App\ContactChannels\TenantContactChannelsStore;
use App\Models\Lead;
use Mockery;
use Tests\TestCase;

class LeadContactActionResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_whatsapp_hidden_when_tenant_disables_channel(): void
    {
        $cfg = static function (bool $uses): TenantContactChannelConfig {
            return new TenantContactChannelConfig(
                usesChannel: $uses,
                publicVisible: false,
                allowedInForms: false,
                businessValue: '',
                sortOrder: 10,
            );
        };

        $store = Mockery::mock(TenantContactChannelsStore::class);
        $store->shouldReceive('resolvedState')
            ->with(1)
            ->andReturn([
                ContactChannelType::Phone->value => $cfg(true),
                ContactChannelType::Whatsapp->value => $cfg(false),
                ContactChannelType::Telegram->value => $cfg(false),
                ContactChannelType::Vk->value => $cfg(false),
                ContactChannelType::Max->value => $cfg(false),
            ]);

        $resolver = new LeadContactActionResolver($store);

        $lead = new Lead([
            'tenant_id' => 1,
            'phone' => '+79991234567',
            'preferred_contact_channel' => ContactChannelType::Phone->value,
            'preferred_contact_value' => '+79991234567',
            'visitor_contact_channels_json' => [
                ['type' => 'phone', 'value' => '+79991234567'],
            ],
        ]);
        $lead->id = 10;

        $types = array_column($resolver->orderedActionsForLead($lead), 'type');

        $this->assertContains('call', $types);
        $this->assertNotContains(ContactChannelType::Whatsapp->value, $types);
    }
}
