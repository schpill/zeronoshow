<x-mail::message>
# Crédit appels faible

Votre solde d'appels Léo est faible.

- Solde restant : {{ number_format($balanceCents / 100, 2, ',', ' ') }} €

Pensez à recharger votre budget pour éviter l'interruption du service.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
