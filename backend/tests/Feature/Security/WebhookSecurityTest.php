<?php

namespace Tests\Feature\Security;

use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tampered_twilio_webhook_requests_are_rejected_with_a_warning(): void
    {
        Log::spy();

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('validateWebhookSignature')->once()->andReturnFalse();
        app()->instance(SmsServiceInterface::class, $service);

        $this->post('/api/v1/webhooks/twilio', [
            'MessageSid' => 'SM-tampered',
        ])->assertForbidden();

        Log::shouldHaveReceived('warning')->once();
    }
}
