<?php

namespace Tests\Unit\Models;

use App\Models\Reservation;
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase
{
    public function test_it_casts_boolean_flags(): void
    {
        $reservation = new Reservation([
            'phone_verified' => 1,
            'reminder_2h_sent' => 0,
        ]);

        $this->assertTrue($reservation->phone_verified);
        $this->assertFalse($reservation->reminder_2h_sent);
    }
}
