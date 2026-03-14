<?php

namespace Tests\Unit\Services;

use App\Models\ReviewRequest;
use App\Services\ReviewLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Str::createRandomStringsNormally();

        parent::tearDown();
    }

    public function test_it_builds_a_google_review_url(): void
    {
        $service = app(ReviewLinkService::class);

        $this->assertSame(
            'https://search.google.com/local/writereview?placeid=ChIJ123456789',
            $service->buildGoogleReviewUrl('ChIJ123456789'),
        );
    }

    public function test_it_builds_a_tripadvisor_review_url(): void
    {
        $service = app(ReviewLinkService::class);

        $this->assertSame(
            'https://www.tripadvisor.fr/UserReviewEdit-12345',
            $service->buildTripadvisorUrl('12345'),
        );
    }

    public function test_generate_short_code_returns_an_eight_character_lowercase_alphanumeric_code(): void
    {
        $service = app(ReviewLinkService::class);

        $code = $service->generateShortCode();

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{8}$/', $code);
        $this->assertSame(strtolower($code), $code);
    }

    public function test_generate_short_code_retries_until_it_finds_a_unique_code(): void
    {
        ReviewRequest::factory()->create([
            'short_code' => 'aaaa1111',
        ]);

        $sequence = ['AAAA1111', 'BBBB2222'];

        Str::createRandomStringsUsing(function () use (&$sequence): string {
            return array_shift($sequence) ?? 'CCCC3333';
        });

        $service = app(ReviewLinkService::class);

        $this->assertSame('bbbb2222', $service->generateShortCode());
    }
}
