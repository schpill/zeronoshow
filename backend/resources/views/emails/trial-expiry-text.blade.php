@php
    $trialEndsAt = $business->trial_ends_at?->copy()->timezone('Europe/Paris');
@endphp
Bonjour {{ $business->name }},

Votre essai ZeroNoShow prendra fin le {{ $trialEndsAt?->translatedFormat('d F Y à H\hi') }}.

Activez votre abonnement pour continuer à créer vos réservations:
{{ $subscriptionUrl }}

Si vous avez déjà souscrit, ignorez cet email.
