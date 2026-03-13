<?php

namespace Tests\Feature\Leo;

use App\Models\Business;
use App\Services\Leo\LeoGeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeoGeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_personalises_the_remote_prompt_with_bot_and_establishment_names(): void
    {
        config()->set('services.gemini.api_key', 'gemini-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        $business = Business::factory()->create([
            'name' => 'Atelier Marais',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => 'Réponse Gemini',
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $service = app(LeoGeminiService::class);

        $response = $service->ask($business->id, 'Milo', 'Que se passe-t-il aujourd’hui ?');

        $this->assertSame('Réponse Gemini', $response);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            $prompt = (string) data_get($request->data(), 'contents.0.parts.0.text');

            return str_contains($prompt, 'Tu es Milo')
                && str_contains($prompt, 'Tu gères les réservations pour Atelier Marais')
                && str_contains($prompt, 'Que se passe-t-il aujourd’hui ?');
        });
    }
}
