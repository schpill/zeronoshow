<?php

namespace Tests\Unit\Models;

use App\Models\Business;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    public function test_it_detects_an_active_trial(): void
    {
        $business = new Business([
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->assertTrue($business->isOnActivePlan());
    }
}
