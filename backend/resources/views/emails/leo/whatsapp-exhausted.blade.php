<x-mail::message>
# Crédit Léo WhatsApp épuisé

Bonjour {{ $business->name }},

Votre crédit Léo WhatsApp a atteint **0,00 €**.
Le canal WhatsApp a été automatiquement suspendu pour éviter tout frais supplémentaire.

Pour réactiver le service, veuillez recharger votre compte depuis votre tableau de bord.

<x-mail::button :url="config('app.frontend_url') . '/leo'">
Recharger mon crédit
</x-mail::button>

Merci,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
