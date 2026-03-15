import type { TourStep } from '@/components/help/OnboardingTour.vue'

export const TOUR_STEPS: TourStep[] = [
  {
    title: 'Votre tableau de bord',
    body: "C'est votre centre de contrôle. Consultez vos réservations du jour, le coût SMS et le taux de no-show hebdomadaire.",
    targetSelector: '#dashboard-stats',
    placement: 'bottom',
  },
  {
    title: 'Créer une réservation',
    body: "Utilisez ce formulaire pour ajouter rapidement une réservation. Le score de fiabilité du client s'affiche automatiquement.",
    targetSelector: '#new-reservation-btn',
    placement: 'right',
  },
  {
    title: 'Score de fiabilité',
    body: "Ce badge indique la fiabilité du client : Fiable (≥90%), Moyen (70-89%) ou À risque (<70%). Il détermine le nombre de rappels envoyés.",
    targetSelector: '.reliability-badge',
    placement: 'bottom',
  },
  {
    title: 'Suivi SMS',
    body: "Consultez ici tous les SMS envoyés pour cette réservation : vérification, rappels, et leur statut de livraison.",
    targetSelector: '#sms-logs-section',
    placement: 'top',
  },
  {
    title: 'Widget de réservation',
    body: "Intégrez un formulaire de réservation sur votre site web ou partagez un lien direct. Les réservations apparaissent ici automatiquement.",
    targetSelector: '#leo-connect-btn',
    placement: 'left',
  },
  {
    title: 'Abonnement',
    body: "Gérez votre abonnement et votre statut d'essai gratuit. Vous pouvez mettre à niveau à tout moment.",
    targetSelector: '#subscription-nav',
    placement: 'bottom',
  },
]
