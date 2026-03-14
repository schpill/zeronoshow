<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendReviewRequestJob;
use App\Models\ReviewRequest;
use App\Models\SmsLog;
use App\Services\Contracts\SmsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SendReviewRequestJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_sms_with_correct_short_url_and_marks_request_as_sent(): void
    {
        config(['app.url' => 'https://zeronoshow.test']);

        $reviewRequest = ReviewRequest::factory()->create([
            'status' => 'pending',
            'short_code' => 'abc12345',
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldReceive('send')->once()->andReturnUsing(fn (SmsLog $smsLog): SmsLog => $smsLog);

        (new SendReviewRequestJob($reviewRequest->id))->handle($service);

        $this->assertDatabaseHas('review_requests', [
            'id' => $reviewRequest->id,
            'status' => 'sent',
        ]);

        $log = SmsLog::query()->where('reservation_id', $reviewRequest->reservation_id)->latest('created_at')->firstOrFail();
        $this->assertSame('review_request', $log->type);
        $this->assertStringContainsString('https://zeronoshow.test/r/abc12345', $log->body);
    }

    public function test_it_is_idempotent_when_request_is_already_sent(): void
    {
        $reviewRequest = ReviewRequest::factory()->create([
            'status' => 'sent',
            'sent_at' => now()->subMinute(),
        ]);

        $service = Mockery::mock(SmsServiceInterface::class);
        $service->shouldNotReceive('send');

        (new SendReviewRequestJob($reviewRequest->id))->handle($service);

        $this->assertDatabaseCount('sms_logs', 0);
    }

    public function test_it_resets_status_to_pending_when_job_fails(): void
    {
        $reviewRequest = ReviewRequest::factory()->create([
            'status' => 'pending',
        ]);

        (new SendReviewRequestJob($reviewRequest->id))->failed(new RuntimeException('twilio down'));

        $this->assertDatabaseHas('review_requests', [
            'id' => $reviewRequest->id,
            'status' => 'pending',
            'sent_at' => null,
        ]);
    }
}
