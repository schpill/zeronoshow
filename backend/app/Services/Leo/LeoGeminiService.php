<?php

namespace App\Services\Leo;

use App\Models\Business;
use Illuminate\Support\Facades\Http;

class LeoGeminiService
{
    public function __construct(
        private readonly GetTodayStatsTool $todayStatsTool,
        private readonly GetPendingReservationsTool $pendingReservationsTool,
        private readonly GetUpcomingReservationsTool $upcomingReservationsTool,
        private readonly GetCancelledReservationsTool $cancelledReservationsTool,
        private readonly GetReservationDetailsTool $reservationDetailsTool,
    ) {}

    public function ask(string $businessId, string $botName, string $userMessage): string
    {
        $business = Business::query()->findOrFail($businessId);

        if ((string) config('services.gemini.api_key') === '') {
            return $this->localFallback($businessId, $business->name, $botName, $userMessage);
        }

        $prompt = $this->buildPrompt($botName, $business->name, $userMessage);

        $response = Http::post(
            sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                config('services.gemini.model', 'gemini-2.5-flash'),
                config('services.gemini.api_key'),
            ),
            [
                'contents' => [[
                    'parts' => [[
                        'text' => $prompt,
                    ]],
                ]],
            ],
        );

        if (! $response->successful()) {
            return $this->localFallback($businessId, $business->name, $botName, $userMessage);
        }

        return (string) data_get($response->json(), 'candidates.0.content.parts.0.text', $this->localFallback($businessId, $business->name, $botName, $userMessage));
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

            return sprintf(
                '%s pour %s: %d réservations aujourd’hui, %d confirmées, %d en attente, %d annulées.',
                $botName,
                $businessName,
                $stats['total'],
                $stats['confirmed'],
                $stats['pending'],
                $stats['cancelled'],
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
}
