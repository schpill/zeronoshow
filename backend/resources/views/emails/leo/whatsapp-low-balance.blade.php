<x-mail::message>
# Crédit Léo WhatsApp faible

Bonjour {{ $business->name }},

Le solde de votre portefeuille Léo WhatsApp est inférieur à **1,00 €**.
Votre solde actuel est de **{{ number_format($balanceCents / 100, 2, ',', ' ') }} €**.

Une fois le solde épuisé, le canal WhatsApp sera automatiquement suspendu.

<x-mail::button :url="config('app.frontend_url') . '/leo'">
Recharger mon crédit
</x-mail::button>

Merci,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
