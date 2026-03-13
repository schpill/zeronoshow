<?php

namespace App\Http\Controllers;

use App\Jobs\RecalculateReliabilityScore;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ConfirmationController extends Controller
{
    public function show(string $token): Response
    {
        $reservation = $this->findReservation($token, withBusiness: true);

        if (! $reservation) {
            return response()->view('confirmation.result', [
                'title' => 'Lien invalide',
                'message' => 'Ce lien de confirmation est invalide.',
            ], 404);
        }

        if ($reservation->token_expires_at && $reservation->token_expires_at->isPast()) {
            return response()->view('confirmation.result', [
                'title' => 'Lien expiré',
                'message' => 'Ce lien a expiré.',
            ], 410);
        }

        if (in_array($reservation->status, ['confirmed', 'cancelled_by_client'], true)) {
            return response()->view('confirmation.result', [
                'title' => 'Déjà traité',
                'message' => 'Vous avez déjà répondu à cette réservation.',
            ]);
        }

        return response()->view('confirmation.show', [
            'reservation' => $reservation,
        ]);
    }

    public function cancel(string $token): Response
    {
        return $this->processAction('cancel', $token);
    }

    public function confirm(Request $request, string $token): Response
    {
        $validated = $request->validate([
            'action' => ['required', 'in:confirm,cancel'],
        ]);

        return $this->processAction($validated['action'], $token);
    }

    private function processAction(string $action, string $token): Response
    {
        $reservation = $this->findReservation($token);

        if (! $reservation) {
            return response()->view('confirmation.result', [
                'title' => 'Lien invalide',
                'message' => 'Ce lien de confirmation est invalide.',
            ], 404);
        }

        if ($reservation->token_expires_at && $reservation->token_expires_at->isPast()) {
            return response()->view('confirmation.result', [
                'title' => 'Lien expiré',
                'message' => 'Ce lien a expiré.',
            ], 410);
        }

        if ($reservation->status === 'confirmed') {
            return $action === 'confirm'
                ? response()->view('confirmation.result', [
                    'title' => 'Réservation déjà confirmée',
                    'message' => 'Vous avez déjà confirmé ce rendez-vous.',
                ])
                : response()->view('confirmation.result', [
                    'title' => 'Déjà traité',
                    'message' => 'Cette réservation ne peut plus être modifiée.',
                ], 410);
        }

        if ($reservation->status === 'cancelled_by_client') {
            return $action === 'cancel'
                ? response()->view('confirmation.result', [
                    'title' => 'Réservation déjà annulée',
                    'message' => 'Vous avez déjà annulé ce rendez-vous.',
                ])
                : response()->view('confirmation.result', [
                    'title' => 'Déjà traité',
                    'message' => 'Cette réservation ne peut plus être modifiée.',
                ], 410);
        }

        if (in_array($reservation->status, ['show', 'no_show', 'cancelled_no_confirmation'], true)) {
            return response()->view('confirmation.result', [
                'title' => 'Déjà traité',
                'message' => 'Cette réservation ne peut plus être modifiée.',
            ], 410);
        }

        DB::transaction(function () use ($reservation, $action): void {
            $reservation->update([
                'status' => $action === 'confirm' ? 'confirmed' : 'cancelled_by_client',
                'status_changed_at' => now(),
            ]);
        });

        RecalculateReliabilityScore::dispatch($reservation->customer_id);

        return response()->view('confirmation.result', [
            'title' => $action === 'confirm' ? 'Réservation confirmée' : 'Réservation annulée',
            'message' => $action === 'confirm'
                ? 'Merci, votre présence est confirmée.'
                : 'Votre réservation a bien été annulée.',
        ]);
    }

    private function findReservation(string $token, bool $withBusiness = false): ?Reservation
    {
        $query = Reservation::query();

        if ($withBusiness) {
            $query->with('business');
        }

        return $query
            ->where('confirmation_token', $token)
            ->first();
    }
}
