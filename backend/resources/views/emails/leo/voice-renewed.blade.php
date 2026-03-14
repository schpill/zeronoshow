<x-mail::message>
# Crédits appels renouvelés

Votre budget mensuel appels Léo a été renouvelé.

- Montant rechargé : {{ number_format($amountCents / 100, 2, ',', ' ') }} €
- Nouveau solde : {{ number_format($newBalanceCents / 100, 2, ',', ' ') }} €

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
