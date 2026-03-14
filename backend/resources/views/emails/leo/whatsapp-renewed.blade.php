<x-mail::message>
# Crédit Léo WhatsApp renouvelé

Bonjour {{ $business->name }},

Bonne nouvelle ! Votre budget mensuel WhatsApp a été automatiquement renouvelé.

- **Montant rechargé :** {{ number_format($amountCents / 100, 2, ',', ' ') }} €
- **Nouveau solde total :** {{ number_format($newBalanceCents / 100, 2, ',', ' ') }} €
- **Prochain renouvellement :** le 1er du mois prochain

Votre assistant Léo est prêt à répondre à vos messages sur WhatsApp.

<x-mail::button :url="config('app.frontend_url') . '/leo'">
Voir mon crédit
</x-mail::button>

Merci,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
