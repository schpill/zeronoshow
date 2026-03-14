<?php

namespace Tests\Unit\Services;

use App\Exceptions\InvalidGuestTokenException;
use App\Services\GuestToken;
use Tests\TestCase;

class GuestTokenTest extends TestCase
{
    private GuestToken $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GuestToken::class);
    }

    public function test_issue_returns_a_non_empty_encrypted_token(): void
    {
        $token = $this->service->issue('+33612345678', 'business-uuid-123');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_verify_decodes_a_valid_token(): void
    {
        $token = $this->service->issue('+33612345678', 'business-uuid-123');
        $payload = $this->service->verify($token);

        $this->assertSame('+33612345678', $payload['phone']);
        $this->assertSame('business-uuid-123', $payload['business_id']);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function test_verify_throws_on_tampered_token(): void
    {
        $token = $this->service->issue('+33612345678', 'business-uuid-123');
        $tampered = $token.'TAMPERED';

        $this->expectException(InvalidGuestTokenException::class);
        $this->service->verify($tampered);
    }

    public function test_verify_throws_on_expired_token(): void
    {
        $this->travelTo(now()->subHour());
        $token = $this->service->issue('+33612345678', 'business-uuid-123');
        $this->travelBack();

        $this->expectException(InvalidGuestTokenException::class);
        $this->service->verify($token);
    }

    public function test_verify_throws_on_completely_invalid_string(): void
    {
        $this->expectException(InvalidGuestTokenException::class);
        $this->service->verify('not-a-valid-token');
    }
}
