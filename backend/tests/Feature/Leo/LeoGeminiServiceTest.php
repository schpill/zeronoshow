<?php

namespace Tests\Feature\Leo;

use App\Leo\Tools\LeoGeminiService;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeoGeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_executes_gemini_function_calls_with_leo_tools_before_returning_the_final_text(): void
    {
        config()->set('services.gemini.api_key', 'gemini-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        $business = Business::factory()->create([
            'name' => 'Atelier Marais',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => [
                            'role' => 'model',
                            'parts' => [[
                                'functionCall' => [
                                    'name' => 'get_today_stats',
                                    'args' => [],
                                ],
                            ]],
                        ],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'text' => 'Résumé réel du jour.',
                            ]],
                        ],
                    ]],
                ], 200),
        ]);

        $service = app(LeoGeminiService::class);

        $response = $service->ask($business->id, 'Milo', 'Quel est le bilan du jour immédiatement ?');

        $this->assertSame('Résumé réel du jour.', $response);

        Http::assertSentCount(2);
        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            return data_get($request->data(), 'tools.0.functionDeclarations.0.name') === 'get_today_stats'
                && data_get($request->data(), 'tools.0.functionDeclarations.4.name') === 'get_reservation_details';
        });
        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return false;
            }

            return data_get($request->data(), 'contents.2.parts.0.functionResponse.name') === 'get_today_stats'
                && data_get($request->data(), 'contents.2.parts.0.functionResponse.response.total') === 0
                && data_get($request->data(), 'contents.2.parts.0.functionResponse.response.score_avg') === null;
        });
    }

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

    public function test_it_declares_tools_and_handles_a_function_call_round_trip(): void
    {
        CarbonImmutable::setTestNow('2026-03-13 10:00:00');

        config()->set('services.gemini.api_key', 'gemini-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        $business = Business::factory()->create([
            'name' => 'Atelier Marais',
        ]);
        $customer = Customer::factory()->create([
            'reliability_score' => 82.5,
        ]);

        Reservation::factory()->create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'scheduled_at' => now()->addHour(),
            'status' => 'confirmed',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'functionCall' => [
                                    'name' => 'get_today_stats',
                                    'args' => new \stdClass,
                                ],
                            ]],
                        ],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'text' => 'Il y a 1 réservation confirmée aujourd’hui.',
                            ]],
                        ],
                    ]],
                ], 200),
        ]);

        $service = app(LeoGeminiService::class);

        $response = $service->ask($business->id, 'Milo', 'Donne-moi les stats du jour');

        $this->assertSame('Il y a 1 réservation confirmée aujourd’hui.', $response);

        Http::assertSentCount(2);

        Http::assertSent(function ($request): bool {
            $toolNames = collect(data_get($request->data(), 'tools.0.functionDeclarations', []))
                ->pluck('name')
                ->all();

            return str_contains($request->url(), 'generativelanguage.googleapis.com')
                && $toolNames === [
                    'get_today_stats',
                    'get_pending_reservations',
                    'get_upcoming_reservations',
                    'get_cancelled_reservations',
                    'get_reservation_details',
                ];
        });

        Http::assertSent(function ($request): bool {
            $parts = data_get($request->data(), 'contents.2.parts', []);
            $functionResponse = $parts[0]['functionResponse'] ?? null;

            return str_contains($request->url(), 'generativelanguage.googleapis.com')
                && data_get($functionResponse, 'name') === 'get_today_stats'
                && data_get($functionResponse, 'response.score_avg') === 82.5
                && data_get($functionResponse, 'response.confirmed') === 1;
        });

        CarbonImmutable::setTestNow();
    }

    public function test_it_stops_after_two_function_calls_and_falls_back_locally(): void
    {
        CarbonImmutable::setTestNow('2026-03-13 10:00:00');

        config()->set('services.gemini.api_key', 'gemini-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        $business = Business::factory()->create([
            'name' => 'Atelier Marais',
        ]);

        Reservation::factory()->create([
            'business_id' => $business->id,
            'scheduled_at' => now()->addHour(),
            'status' => 'confirmed',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'functionCall' => [
                                    'name' => 'get_today_stats',
                                    'args' => new \stdClass,
                                ],
                            ]],
                        ],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'functionCall' => [
                                    'name' => 'get_pending_reservations',
                                    'args' => new \stdClass,
                                ],
                            ]],
                        ],
                    ]],
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'functionCall' => [
                                    'name' => 'get_upcoming_reservations',
                                    'args' => new \stdClass,
                                ],
                            ]],
                        ],
                    ]],
                ], 200),
        ]);

        $service = app(LeoGeminiService::class);

        $response = $service->ask($business->id, 'Milo', 'Quel est le bilan du jour aujourd’hui ?');

        $this->assertSame(
            'Milo pour Atelier Marais: 1 réservations aujourd’hui, 1 confirmées, 0 en attente, 0 annulées.',
            $response,
        );

        Http::assertSentCount(3);

        CarbonImmutable::setTestNow();
    }
}
