<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    public function test_it_returns_the_expected_score_tier(): void
    {
        $customer = new Customer(['reliability_score' => 72]);

        $this->assertSame('average', $customer->getScoreTier());
    }

    public function test_it_returns_at_risk_for_null_score(): void
    {
        $customer = new Customer(['reliability_score' => null]);

        $this->assertSame('at_risk', $customer->getScoreTier());
    }
}
