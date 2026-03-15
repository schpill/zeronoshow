<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\SmsLog;
use App\Models\WaitlistEntry;
use App\Models\WidgetSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCreperie();
        $this->seedCoiffeur();
        $this->seedInfirmiere();

        $this->command->info('✓ DemoSeeder terminé — 3 établissements dans l\'Oise, clients, RDV, SMS logs.');
    }

    // ─── La Galette Picarde — Crêperie, Senlis (60300) ────────────────────

    private function seedCreperie(): void
    {
        $business = Business::updateOrCreate(
            ['email' => 'galette.picarde@demo.znz'],
            [
                'name' => 'La Galette Picarde',
                'password' => Hash::make('password123'),
                'phone' => '+33344532010',
                'timezone' => 'Europe/Paris',
                'subscription_status' => 'active',
                'trial_ends_at' => now()->subDays(20), // essai terminé, passé en abonnement payant
                'review_requests_enabled' => true,
                'review_platform' => 'google',
                'review_delay_hours' => 2,
                'google_place_id' => 'ChIJsenlis60300galette',
                'public_token' => Str::uuid(),
                'onboarding_completed_at' => now()->subDays(45),
                'waitlist_enabled' => true,
                'waitlist_notification_window_minutes' => 30,
            ]
        );

        WidgetSetting::updateOrCreate(
            ['business_id' => $business->id],
            [
                'accent_colour' => '#b45309',
                'max_party_size' => 8,
                'advance_booking_days' => 30,
                'same_day_cutoff_minutes' => 120,
                'is_enabled' => true,
            ]
        );

        // Repartir de zéro pour les réservations à chaque seed
        $business->reservations()->delete();

        $c = [
            'marie' => $this->customer('+33612340101', shows: 7, noShows: 0, vip: true,
                notes: 'Table habituelle près de la fenêtre', tableNotes: 'Coin fenêtre côté rue'),
            'jean_pierre' => $this->customer('+33612340102', shows: 4, noShows: 1),
            'sophie' => $this->customer('+33612340103', shows: 3, noShows: 0),
            'bernard' => $this->customer('+33612340104', shows: 2, noShows: 2),
            'nathalie' => $this->customer('+33612340105', shows: 1, noShows: 4,
                blacklisted: true, notes: 'No-shows répétés sans prévenir'),
            'thierry' => $this->customer('+33612340106', shows: 5, noShows: 0, vip: true),
            'isabelle' => $this->customer('+33612340107', shows: 2, noShows: 1,
                birthdayMonth: 6, birthdayDay: 15),
            'christophe' => $this->customer('+33612340108', shows: 0, noShows: 0),
        ];

        // Passées
        $this->resa($business, $c['marie'], 'Marie Dupont', daysAgo: 45, hour: 12, guests: 4, status: 'show', source: 'widget');
        $this->resa($business, $c['jean_pierre'], 'Jean-Pierre Martin', daysAgo: 38, hour: 19, guests: 3, status: 'show');
        $this->resa($business, $c['nathalie'], 'Nathalie Moreau', daysAgo: 30, hour: 20, guests: 2, status: 'no_show');
        $this->resa($business, $c['bernard'], 'Bernard Rousseau', daysAgo: 22, hour: 12, guests: 6, status: 'show', source: 'widget');
        $this->resa($business, $c['thierry'], 'Thierry Bonnet', daysAgo: 18, hour: 19, guests: 2, status: 'show');
        $this->resa($business, $c['sophie'], 'Sophie Leclerc', daysAgo: 15, hour: 12, guests: 2, status: 'cancelled');
        $this->resa($business, $c['isabelle'], 'Isabelle Petit', daysAgo: 10, hour: 20, guests: 3, status: 'no_show');
        $this->resa($business, $c['marie'], 'Marie Dupont', daysAgo: 7, hour: 12, guests: 4, status: 'show', source: 'widget');
        $this->resa($business, $c['jean_pierre'], 'Jean-Pierre Martin', daysAgo: 3, hour: 19, guests: 2, status: 'show');

        // À venir
        $this->resa($business, $c['thierry'], 'Thierry Bonnet', daysAgo: -3, hour: 20, guests: 2, status: 'confirmed');
        $this->resa($business, $c['sophie'], 'Sophie Leclerc', daysAgo: -7, hour: 12, guests: 4, status: 'confirmed', source: 'widget');
        $this->resa($business, $c['christophe'], 'Christophe Girard', daysAgo: -14, hour: 19, guests: 3, status: 'pending_verification');

        // Liste d'attente — 3 entrées pour tester la vue waitlist
        $waitlist = [
            ['name' => 'Famille Leroy',  'phone' => '+33612340190', 'size' => 4, 'status' => 'pending',  'priority' => 0],
            ['name' => 'Aurélie Vidal',  'phone' => '+33612340191', 'size' => 2, 'status' => 'notified', 'priority' => 1],
            ['name' => 'Marc Fontaine',  'phone' => '+33612340192', 'size' => 3, 'status' => 'pending',  'priority' => 2],
        ];
        foreach ($waitlist as $entry) {
            WaitlistEntry::updateOrCreate(
                ['business_id' => $business->id, 'client_phone' => $entry['phone']],
                [
                    'slot_date' => now()->addDays(2)->toDateString(),
                    'slot_time' => '19:30:00',
                    'client_name' => $entry['name'],
                    'party_size' => $entry['size'],
                    'priority_order' => $entry['priority'],
                    'status' => $entry['status'],
                    'channel' => 'sms',
                    'notified_at' => $entry['status'] === 'notified' ? now()->subHour() : null,
                    'expires_at' => $entry['status'] === 'notified' ? now()->addMinutes(30) : null,
                    'confirmation_token' => Str::random(64),
                ]
            );
        }
    }

    // ─── Tif & Style — Coiffeur, Beauvais (60000) ─────────────────────────

    private function seedCoiffeur(): void
    {
        $business = Business::updateOrCreate(
            ['email' => 'tif.style@demo.znz'],
            [
                'name' => 'Tif & Style',
                'password' => Hash::make('password123'),
                'phone' => '+33344532020',
                'timezone' => 'Europe/Paris',
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays(12),
                'review_requests_enabled' => false,
                'public_token' => Str::uuid(),
                'onboarding_completed_at' => now()->subDays(2),
                'waitlist_enabled' => false,
            ]
        );

        $business->reservations()->delete();

        $c = [
            'virginie' => $this->customer('+33612340201', shows: 8, noShows: 0, vip: true,
                notes: 'Cliente depuis 5 ans. Balayage mensuel.', birthdayMonth: 3, birthdayDay: 8),
            'pascale' => $this->customer('+33612340202', shows: 5, noShows: 1),
            'kevin' => $this->customer('+33612340203', shows: 3, noShows: 2),
            'sylvie' => $this->customer('+33612340204', shows: 2, noShows: 0),
            'eric' => $this->customer('+33612340205', shows: 1, noShows: 3),
            'jennifer' => $this->customer('+33612340206', shows: 4, noShows: 1,
                tableNotes: 'Allergie ammoniaqués — gamme bio uniquement'),
            'romain' => $this->customer('+33612340207', shows: 0, noShows: 1,
                blacklisted: true, notes: 'Annulation le jour même sans prévenir'),
            'amelie' => $this->customer('+33612340208', shows: 0, noShows: 0),
        ];

        // Passées
        $this->resa($business, $c['virginie'], 'Virginie Lambert', daysAgo: 60, hour: 10, guests: 1, status: 'show');
        $this->resa($business, $c['pascale'], 'Pascale Renard', daysAgo: 45, hour: 14, guests: 1, status: 'show');
        $this->resa($business, $c['eric'], 'Éric Chevalier', daysAgo: 35, hour: 10, guests: 1, status: 'no_show');
        $this->resa($business, $c['jennifer'], 'Jennifer Blanc', daysAgo: 28, hour: 11, guests: 1, status: 'show');
        $this->resa($business, $c['kevin'], 'Kévin Morel', daysAgo: 21, hour: 9, guests: 1, status: 'no_show');
        $this->resa($business, $c['sylvie'], 'Sylvie Thomas', daysAgo: 14, hour: 10, guests: 1, status: 'show');
        $this->resa($business, $c['romain'], 'Romain Gauthier', daysAgo: 7, hour: 9, guests: 1, status: 'cancelled');
        $this->resa($business, $c['virginie'], 'Virginie Lambert', daysAgo: 2, hour: 10, guests: 1, status: 'show');

        // À venir
        $this->resa($business, $c['pascale'], 'Pascale Renard', daysAgo: -3, hour: 14, guests: 1, status: 'confirmed');
        $this->resa($business, $c['jennifer'], 'Jennifer Blanc', daysAgo: -5, hour: 11, guests: 1, status: 'confirmed');
        $this->resa($business, $c['amelie'], 'Amélie Faure', daysAgo: -10, hour: 10, guests: 1, status: 'pending_verification');
    }

    // ─── Cabinet Infirmier Lebrun — Infirmière libérale, Noyon (60400) ────

    private function seedInfirmiere(): void
    {
        $business = Business::updateOrCreate(
            ['email' => 'infirmier.lebrun@demo.znz'],
            [
                'name' => 'Cabinet Infirmier Lebrun',
                'password' => Hash::make('password123'),
                'phone' => '+33344532030',
                'timezone' => 'Europe/Paris',
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->subDays(3), // essai expiré → teste le flux 402
                'review_requests_enabled' => false,
                'public_token' => Str::uuid(),
                'onboarding_completed_at' => now()->subDays(20),
                'waitlist_enabled' => false,
            ]
        );

        $business->reservations()->delete();

        $c = [
            'madeleine' => $this->customer('+33612340301', shows: 10, noShows: 0, vip: true,
                notes: 'Soins post-opératoires genou gauche.', birthdayMonth: 5, birthdayDay: 22),
            'roger' => $this->customer('+33612340302', shows: 6, noShows: 1,
                notes: 'Diabétique. Prise de sang hebdomadaire.'),
            'henriette' => $this->customer('+33612340303', shows: 4, noShows: 1),
            'pierre_yves' => $this->customer('+33612340304', shows: 2, noShows: 2,
                notes: 'Souvent en retard. Prévenir la veille par SMS.'),
            'georgette' => $this->customer('+33612340305', shows: 3, noShows: 0,
                birthdayMonth: 11, birthdayDay: 3),
            'alain' => $this->customer('+33612340306', shows: 1, noShows: 3,
                notes: 'Annule souvent à la dernière minute.'),
            'colette' => $this->customer('+33612340307', shows: 5, noShows: 0,
                notes: 'Pansement plaie chronique jambe droite — tous les 3 jours.'),
            'gerard' => $this->customer('+33612340308', shows: 0, noShows: 0,
                notes: 'Nouveau patient. Référence Dr Lemaire.'),
        ];

        // Passées
        $this->resa($business, $c['madeleine'], 'Madeleine Lecomte', daysAgo: 20, hour: 8, guests: 1, status: 'show');
        $this->resa($business, $c['roger'], 'Roger Dumont', daysAgo: 17, hour: 9, guests: 1, status: 'show');
        $this->resa($business, $c['alain'], 'Alain Perrin', daysAgo: 14, hour: 10, guests: 1, status: 'no_show');
        $this->resa($business, $c['henriette'], 'Henriette Caron', daysAgo: 10, hour: 8, guests: 1, status: 'show');
        $this->resa($business, $c['colette'], 'Colette Mercier', daysAgo: 7, hour: 9, guests: 1, status: 'show');
        $this->resa($business, $c['pierre_yves'], 'Pierre-Yves Simon', daysAgo: 5, hour: 8, guests: 1, status: 'cancelled');
        $this->resa($business, $c['georgette'], 'Georgette Arnaud', daysAgo: 3, hour: 10, guests: 1, status: 'show');
        $this->resa($business, $c['madeleine'], 'Madeleine Lecomte', daysAgo: 1, hour: 8, guests: 1, status: 'show');

        // À venir (bloqués par l'essai expiré côté front, mais visibles en admin)
        $this->resa($business, $c['roger'], 'Roger Dumont', daysAgo: -2, hour: 9, guests: 1, status: 'confirmed');
        $this->resa($business, $c['colette'], 'Colette Mercier', daysAgo: -4, hour: 8, guests: 1, status: 'confirmed');
        $this->resa($business, $c['gerard'], 'Gérard Vasseur', daysAgo: -7, hour: 10, guests: 1, status: 'pending_verification');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function customer(
        string $phone,
        int $shows,
        int $noShows,
        bool $vip = false,
        bool $blacklisted = false,
        ?string $notes = null,
        ?string $tableNotes = null,
        ?int $birthdayMonth = null,
        ?int $birthdayDay = null,
    ): Customer {
        $total = $shows + $noShows;
        $score = $total > 0 ? round($shows / $total * 100, 1) : null;
        $tier = match (true) {
            $score === null => null,
            $score >= 80.0 => 'excellent',
            $score >= 60.0 => 'good',
            $score >= 40.0 => 'average',
            default => 'at_risk',
        };

        return Customer::updateOrCreate(
            ['phone' => $phone],
            [
                'reservations_count' => $shows + $noShows,
                'shows_count' => $shows,
                'no_shows_count' => $noShows,
                'reliability_score' => $score,
                'score_tier' => $tier,
                'last_calculated_at' => $total > 0 ? now()->subHour() : null,
                'is_vip' => $vip,
                'is_blacklisted' => $blacklisted,
                'notes' => $notes,
                'preferred_table_notes' => $tableNotes,
                'birthday_month' => $birthdayMonth,
                'birthday_day' => $birthdayDay,
                'opted_out' => false,
            ]
        );
    }

    /**
     * Crée une réservation et ses SMS logs associés.
     *
     * daysAgo > 0 = passé · daysAgo < 0 = futur
     */
    private function resa(
        Business $business,
        Customer $customer,
        string $name,
        int $daysAgo,
        int $hour,
        int $guests,
        string $status,
        string $source = 'manual',
    ): Reservation {
        $past = $daysAgo >= 0;
        $scheduledAt = $past
            ? now()->subDays($daysAgo)->setTime($hour, 0, 0)
            : now()->addDays(abs($daysAgo))->setTime($hour, 0, 0);

        $phoneVerified = in_array($status, ['confirmed', 'show', 'no_show', 'cancelled'], true);

        $reservation = Reservation::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => $name,
            'scheduled_at' => $scheduledAt,
            'guests' => $guests,
            'status' => $status,
            'source' => $source,
            'phone_verified' => $phoneVerified,
            'confirmation_token' => (string) Str::uuid(),
            'token_expires_at' => now()->addHours(20),
            'reminder_2h_sent' => in_array($status, ['show', 'no_show'], true),
            'reminder_30m_sent' => in_array($status, ['show', 'no_show'], true),
            'status_changed_at' => in_array($status, ['show', 'no_show', 'cancelled'], true)
                ? $scheduledAt->copy()->addHour()
                : null,
        ]);

        // SMS logs pour les réservations passées vérifiées
        if ($past && $phoneVerified) {
            $this->smsLog($reservation, $business, $customer->phone, 'verification',
                "Bonjour {$name}, confirmez votre RDV du {$scheduledAt->format('d/m à H\hi')} en répondant OUI.",
                daysAgo: $daysAgo + 1
            );

            if (in_array($status, ['show', 'no_show'], true)) {
                $this->smsLog($reservation, $business, $customer->phone, 'reminder_2h',
                    "Rappel : votre RDV est dans 2h ({$scheduledAt->format('H\hi')}). À tout à l'heure !",
                    daysAgo: $daysAgo
                );
            }
        }

        return $reservation;
    }

    private function smsLog(
        Reservation $reservation,
        Business $business,
        string $phone,
        string $type,
        string $body,
        int $daysAgo,
    ): void {
        $sentAt = now()->subDays($daysAgo)->subMinutes(rand(1, 10));

        SmsLog::create([
            'reservation_id' => $reservation->id,
            'business_id' => $business->id,
            'phone' => $phone,
            'type' => $type,
            'body' => $body,
            'twilio_sid' => 'SM'.Str::random(32),
            'status' => 'delivered',
            'cost_eur' => 0.08,
            'queued_at' => $sentAt->copy()->subSeconds(5),
            'sent_at' => $sentAt,
            'delivered_at' => $sentAt->copy()->addSeconds(rand(3, 15)),
            'created_at' => $sentAt,
        ]);
    }
}
