<?php

namespace App\Services\Leo;

use App\Models\Business;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use stdClass;

class LeoGeminiService
{
    private const MAX_FUNCTION_CALLS_PER_TURN = 2;

    public function __construct(
        private readonly GetTodayStatsTool $todayStatsTool,
        private readonly GetPendingReservationsTool $pendingReservationsTool,
        private readonly GetUpcomingReservationsTool $upcomingReservationsTool,
        private readonly GetCancelledReservationsTool $cancelledReservationsTool,
        private readonly GetReservationDetailsTool $reservationDetailsTool,
    ) {}

    public function ask(string $businessId, string $botName, string $userMessage): string
    {
        $businessName = (string) (Business::query()->whereKey($businessId)->value('name') ?? 'votre établissement');

        if ((string) config('services.gemini.api_key') === '') {
            return $this->localFallback($businessId, $businessName, $botName, $userMessage);
        }

        $contents = [[
            'role' => 'user',
            'parts' => [[
                'text' => $this->buildPrompt($botName, $businessName, $userMessage),
            ]],
        ]];

        for ($callCount = 0; $callCount <= self::MAX_FUNCTION_CALLS_PER_TURN; $callCount++) {
            $response = $this->generateContent($contents);

            if (! $response->successful()) {
                return $this->localFallback($businessId, $businessName, $botName, $userMessage);
            }

            $candidateContent = data_get($response->json(), 'candidates.0.content');

            if (! is_array($candidateContent)) {
                return $this->localFallback($businessId, $businessName, $botName, $userMessage);
            }

            $parts = collect(data_get($candidateContent, 'parts', []));
            $functionCall = $this->firstFunctionCall($parts);

            if ($functionCall === null) {
                $text = $this->extractText($parts);

                return $text !== ''
                    ? $text
                    : $this->localFallback($businessId, $businessName, $botName, $userMessage);
            }

            if ($callCount === self::MAX_FUNCTION_CALLS_PER_TURN) {
                break;
            }

            $functionName = (string) data_get($functionCall, 'name');
            $arguments = data_get($functionCall, 'args', []);

            $contents[] = $candidateContent;
            $contents[] = [
                'role' => 'user',
                'parts' => [[
                    'functionResponse' => [
                        'name' => $functionName,
                        'response' => $this->dispatchTool(
                            $businessId,
                            $functionName,
                            is_array($arguments) ? $arguments : [],
                        ),
                    ],
                ]],
            ];
        }

        return $this->localFallback($businessId, $businessName, $botName, $userMessage);
    }

    private function buildPrompt(string $botName, string $businessName, string $userMessage): string
    {
        $systemPrompt = str_replace(
            'Léo',
            $botName,
            'Tu es Léo, un assistant de réservation. Réponds en français de façon concise et utile.'
        );

        return sprintf(
            '%s Tu gères les réservations pour %s. Message utilisateur: %s',
            $systemPrompt,
            $businessName,
            $userMessage,
        );
    }

    private function localFallback(string $businessId, string $businessName, string $botName, string $userMessage): string
    {
        $message = mb_strtolower($userMessage);

        if (str_contains($message, 'aujourd')) {
            $stats = $this->todayStatsTool->execute($businessId);
            $scoreAverage = $stats['score_avg'];
            $score = $scoreAverage === null ? '' : sprintf(' Score moyen: %.2f.', $scoreAverage);

            return sprintf(
                '%s pour %s: %d réservations aujourd’hui, %d confirmées, %d en attente, %d annulées.%s',
                $botName,
                $businessName,
                $stats['total'],
                $stats['confirmed'],
                $stats['pending'],
                $stats['cancelled'],
                $score,
            );
        }

        if (str_contains($message, 'attente')) {
            $pending = $this->pendingReservationsTool->execute($businessId);

            return $pending === []
                ? 'Aucun client en attente aujourd’hui.'
                : 'En attente: '.collect($pending)->map(fn (array $item): string => "{$item['time']} {$item['name']}")->implode(', ');
        }

        if (str_contains($message, 'annul')) {
            $cancelled = $this->cancelledReservationsTool->execute($businessId);

            return $cancelled === []
                ? 'Aucune annulation aujourd’hui.'
                : 'Annulations: '.collect($cancelled)->map(fn (array $item): string => "{$item['time']} {$item['name']}")->implode(', ');
        }

        if (str_contains($message, 'prochain') || str_contains($message, 'avenir')) {
            $upcoming = $this->upcomingReservationsTool->execute($businessId);

            return $upcoming === []
                ? 'Aucune réservation à venir.'
                : 'À venir: '.collect($upcoming)->map(fn (array $item): string => "{$item['time']} {$item['name']}")->implode(', ');
        }

        $details = $this->reservationDetailsTool->execute($businessId, $userMessage);

        if ($details !== []) {
            $first = $details[0];

            return sprintf(
                '%s à %s pour %d personnes. Statut: %s.',
                $first['name'],
                $first['time'],
                $first['guests'],
                $first['status'],
            );
        }

        return sprintf(
            '%s est prêt pour %s. Demandez-moi les stats du jour, les réservations en attente, les annulations ou les détails d’une réservation.',
            $botName,
            $businessName,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $contents
     */
    private function generateContent(array $contents): Response
    {
        return Http::post(
            sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                config('services.gemini.model', 'gemini-2.5-flash'),
                config('services.gemini.api_key'),
            ),
            [
                'contents' => $contents,
                'tools' => [[
                    'functionDeclarations' => $this->functionDeclarations(),
                ]],
            ],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function functionDeclarations(): array
    {
        return [
            [
                'name' => 'get_today_stats',
                'description' => 'Retourne les statistiques du jour pour l établissement actif.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => new stdClass,
                ],
            ],
            [
                'name' => 'get_pending_reservations',
                'description' => 'Retourne les reservations en attente aujourd hui.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => new stdClass,
                ],
            ],
            [
                'name' => 'get_upcoming_reservations',
                'description' => 'Retourne les prochaines reservations a venir.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'limit' => [
                            'type' => 'INTEGER',
                            'description' => 'Nombre maximum de reservations a retourner.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_cancelled_reservations',
                'description' => 'Retourne les reservations annulees aujourd hui.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => new stdClass,
                ],
            ],
            [
                'name' => 'get_reservation_details',
                'description' => 'Recherche une reservation du jour par nom ou heure.',
                'parameters' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'query' => [
                            'type' => 'STRING',
                            'description' => 'Nom du client ou heure HH:MM.',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    private function dispatchTool(string $businessId, string $functionName, array $arguments): array
    {
        return match ($functionName) {
            'get_today_stats' => $this->todayStatsTool->execute($businessId),
            'get_pending_reservations' => $this->pendingReservationsTool->execute($businessId),
            'get_upcoming_reservations' => $this->upcomingReservationsTool->execute(
                $businessId,
                max(1, (int) ($arguments['limit'] ?? 5)),
            ),
            'get_cancelled_reservations' => $this->cancelledReservationsTool->execute($businessId),
            'get_reservation_details' => $this->reservationDetailsTool->execute(
                $businessId,
                (string) ($arguments['query'] ?? ''),
            ),
            default => ['error' => 'unsupported_function'],
        };
    }

    /**
     * @param  Collection<int, mixed>  $parts
     * @return array<string, mixed>|null
     */
    private function firstFunctionCall(Collection $parts): ?array
    {
        $functionCall = $parts
            ->map(fn (mixed $part): mixed => is_array($part) ? data_get($part, 'functionCall') : null)
            ->first(fn (mixed $part): bool => is_array($part));

        return is_array($functionCall) ? $functionCall : null;
    }

    /**
     * @param  Collection<int, mixed>  $parts
     */
    private function extractText(Collection $parts): string
    {
        return $parts
            ->map(fn (mixed $part): ?string => is_array($part) ? data_get($part, 'text') : null)
            ->filter()
            ->implode("\n");
    }
}
