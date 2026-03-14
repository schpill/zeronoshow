<?php

namespace Tests\Feature\Voice;

use App\Jobs\PlaceVoiceCallJob;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReservationObserverVoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_when_score_is_below_threshold(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'voice_credit_cents' => 100,
            'voice_auto_call_enabled' => true,
            'voice_auto_call_score_threshold' => 40,
            'voice_retry_count' => 3,
        ]);
        $customer = Customer::factory()->create([
            'reliability_score' => 20,
        ]);

        $reservation = Reservation::factory()->for($business)->for($customer)->create([
            'status' => 'pending_verification',
        ]);

        Queue::assertPushed(PlaceVoiceCallJob::class, fn (PlaceVoiceCallJob $job) => $job->reservationId === $reservation->id);
    }

    public function test_it_dispatches_when_guests_are_above_threshold(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'voice_credit_cents' => 100,
            'voice_auto_call_enabled' => true,
            'voice_auto_call_min_party_size' => 5,
        ]);

        $reservation = Reservation::factory()->for($business)->create([
            'guests' => 6,
            'status' => 'pending_verification',
        ]);

        Queue::assertPushed(PlaceVoiceCallJob::class, fn (PlaceVoiceCallJob $job) => $job->reservationId === $reservation->id);
    }

    public function test_it_does_not_dispatch_when_auto_call_disabled(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'voice_credit_cents' => 100,
            'voice_auto_call_enabled' => false,
            'voice_auto_call_score_threshold' => 40,
        ]);
        $customer = Customer::factory()->create([
            'reliability_score' => 20,
        ]);

        Reservation::factory()->for($business)->for($customer)->create([
            'status' => 'pending_verification',
        ]);

        Queue::assertNotPushed(PlaceVoiceCallJob::class);
    }

    public function test_it_does_not_dispatch_when_credit_is_insufficient(): void
    {
        Queue::fake();

        $business = Business::factory()->create([
            'voice_credit_cents' => 0,
            'voice_auto_call_enabled' => true,
            'voice_auto_call_score_threshold' => 40,
        ]);
        $customer = Customer::factory()->create([
            'reliability_score' => 20,
        ]);

        Reservation::factory()->for($business)->for($customer)->create([
            'status' => 'pending_verification',
        ]);

        Queue::assertNotPushed(PlaceVoiceCallJob::class);
    }
}
