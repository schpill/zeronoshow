<?php

namespace Tests\Feature\Webhook;

use App\Models\Customer;
use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class TwilioWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_forbidden_for_an_invalid_signature(): void
    {
        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('validateWebhookSignature')->once()->andReturnFalse();
        app()->instance(SmsServiceInterface::class, $service);

        $this->post('/api/v1/webhooks/twilio', [
            'MessageSid' => 'SM123',
        ])->assertForbidden();
    }

    public function test_it_updates_sms_log_as_delivered(): void
    {
        $smsLog = SmsLog::factory()->create([
            'twilio_sid' => 'SM123',
            'status' => 'sent',
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('validateWebhookSignature')->once()->andReturnTrue();
        app()->instance(SmsServiceInterface::class, $service);

        $this->post('/api/v1/webhooks/twilio', [
            'MessageSid' => 'SM123',
            'MessageStatus' => 'delivered',
            'Price' => '-0.0750',
        ])->assertOk();

        $this->assertDatabaseHas('sms_logs', [
            'id' => $smsLog->id,
            'status' => 'delivered',
            'cost_eur' => 0.0750,
        ]);
    }

    public function test_it_updates_sms_log_as_failed(): void
    {
        $smsLog = SmsLog::factory()->create([
            'twilio_sid' => 'SM124',
            'status' => 'sent',
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('validateWebhookSignature')->once()->andReturnTrue();
        app()->instance(SmsServiceInterface::class, $service);

        $this->post('/api/v1/webhooks/twilio', [
            'MessageSid' => 'SM124',
            'MessageStatus' => 'failed',
            'Price' => '-0.0600',
        ])->assertOk();

        $this->assertDatabaseHas('sms_logs', [
            'id' => $smsLog->id,
            'status' => 'failed',
            'cost_eur' => 0.0600,
        ]);
    }

    public function test_it_marks_customer_as_opted_out_on_stop_reply(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '+33612345678',
            'opted_out' => false,
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('validateWebhookSignature')->once()->andReturnTrue();
        app()->instance(SmsServiceInterface::class, $service);

        $this->post('/api/v1/webhooks/twilio', [
            'From' => '+33612345678',
            'Body' => 'STOP',
        ])->assertOk();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'opted_out' => true,
        ]);
    }

    public function test_it_returns_ok_for_unknown_message_sids(): void
    {
        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('validateWebhookSignature')->once()->andReturnTrue();
        app()->instance(SmsServiceInterface::class, $service);

        $this->post('/api/v1/webhooks/twilio', [
            'MessageSid' => 'SM999',
            'MessageStatus' => 'delivered',
        ])->assertOk();
    }

    public function test_it_logs_a_warning_when_the_twilio_signature_is_invalid(): void
    {
        Log::spy();

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('validateWebhookSignature')->once()->andReturnFalse();
        app()->instance(SmsServiceInterface::class, $service);

        $this->post('/api/v1/webhooks/twilio', [
            'MessageSid' => 'SM-invalid',
        ])->assertForbidden();

        Log::shouldHaveReceived('warning')->once();
    }
}
