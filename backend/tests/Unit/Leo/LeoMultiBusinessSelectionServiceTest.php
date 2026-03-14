<?php

namespace Tests\Unit\Leo;

use App\Models\Business;
use App\Models\LeoChannel;
use App\Leo\Tools\LeoMultiBusinessSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LeoMultiBusinessSelectionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_selection_prompt_returns_a_numbered_list(): void
    {
        $firstBusiness = Business::factory()->create(['name' => 'Salon République']);
        $secondBusiness = Business::factory()->create(['name' => 'Salon Bastille']);
        $channels = new Collection([
            LeoChannel::factory()->make(['business_id' => $firstBusiness->id])->setRelation('business', $firstBusiness),
            LeoChannel::factory()->make(['business_id' => $secondBusiness->id])->setRelation('business', $secondBusiness),
        ]);

        $service = new LeoMultiBusinessSelectionService;
        $prompt = $service->buildSelectionPrompt($channels);

        $this->assertStringContainsString('Pour quel établissement ?', $prompt);
        $this->assertStringContainsString('1. Salon République', $prompt);
        $this->assertStringContainsString('2. Salon Bastille', $prompt);
    }

    public function test_parse_selection_accepts_a_number_or_establishment_name(): void
    {
        $firstBusiness = Business::factory()->create(['name' => 'Salon République']);
        $secondBusiness = Business::factory()->create(['name' => 'Salon Bastille']);
        $firstChannel = LeoChannel::factory()->make(['business_id' => $firstBusiness->id])->setRelation('business', $firstBusiness);
        $secondChannel = LeoChannel::factory()->make(['business_id' => $secondBusiness->id])->setRelation('business', $secondBusiness);
        $channels = new Collection([$firstChannel, $secondChannel]);

        $service = new LeoMultiBusinessSelectionService;

        $this->assertSame($secondChannel, $service->parseSelection('2', $channels));
        $this->assertSame($firstChannel, $service->parseSelection('répub', $channels));
        $this->assertNull($service->parseSelection('inconnu', $channels));
    }
}
