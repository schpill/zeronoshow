@php
    $trialEndsAt = $business->trial_ends_at?->copy()->timezone('Europe/Paris');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Essai ZeroNoShow</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; background: #f8fafc; color: #0f172a; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; padding: 32px;">
        <p style="font-size: 12px; letter-spacing: 0.16em; text-transform: uppercase; color: #64748b; margin: 0 0 16px;">
            ZeroNoShow
        </p>
        <h1 style="font-size: 30px; line-height: 1.25; margin: 0 0 16px;">
            Votre essai expire dans 48h
        </h1>
        <p style="font-size: 16px; line-height: 1.6; color: #334155; margin: 0 0 16px;">
            Bonjour {{ $business->name }}, votre essai ZeroNoShow prendra fin le
            <strong>{{ $trialEndsAt?->translatedFormat('d F Y \\à H\\hi') }}</strong>.
        </p>
        <p style="font-size: 16px; line-height: 1.6; color: #334155; margin: 0 0 24px;">
            Activez votre abonnement pour continuer à créer vos réservations et suivre vos no-shows sans interruption.
        </p>
        <p style="margin: 0 0 24px;">
            <a href="{{ $subscriptionUrl }}" style="display: inline-block; background: #10b981; color: #ffffff; text-decoration: none; padding: 14px 20px; border-radius: 16px; font-weight: 600;">
                Voir mon abonnement
            </a>
        </p>
        <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin: 0;">
            Si vous avez déjà souscrit, vous pouvez ignorer cet email.
        </p>
    </div>
</body>
</html>
