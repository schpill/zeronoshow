<?php

namespace Tests\Feature\Widget;

use App\Models\Business;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReservationSourceFilterTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::factory()->create();
        Sanctum::actingAs($this->business);

        Reservation::factory()->create([
            'business_id' => $this->business->id,
            'source' => 'widget',
            'scheduled_at' => now()->addDay(),
        ]);
        Reservation::factory()->create([
            'business_id' => $this->business->id,
            'source' => 'manual',
            'scheduled_at' => now()->addDay(),
        ]);
    }

    public function test_filter_by_widget_returns_only_widget_reservations(): void
    {
        $response = $this->getJson('/api/v1/reservations?source=widget&date='.now()->addDay()->format('Y-m-d'));

        $response->assertOk();
        $reservations = $response->json('reservations');
        $this->assertCount(1, $reservations);
        $this->assertSame('widget', $reservations[0]['source']);
    }

    public function test_filter_by_manual_returns_only_manual_reservations(): void
    {
        $response = $this->getJson('/api/v1/reservations?source=manual&date='.now()->addDay()->format('Y-m-d'));

        $response->assertOk();
        $reservations = $response->json('reservations');
        $this->assertCount(1, $reservations);
        $this->assertSame('manual', $reservations[0]['source']);
    }

    public function test_no_source_filter_returns_all_reservations(): void
    {
        $response = $this->getJson('/api/v1/reservations?date='.now()->addDay()->format('Y-m-d'));

        $response->assertOk();
        $this->assertCount(2, $response->json('reservations'));
    }

    public function test_invalid_source_value_is_ignored_and_returns_all(): void
    {
        $response = $this->getJson('/api/v1/reservations?source=unknown&date='.now()->addDay()->format('Y-m-d'));

        $response->assertOk();
        // Invalid source is silently ignored — returns all reservations
        $this->assertCount(2, $response->json('reservations'));
    }
}
