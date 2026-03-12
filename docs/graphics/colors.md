# Palette de couleurs — ZeroNoShow

> Toutes les couleurs sont issues de la palette **Tailwind CSS** (Emerald + Slate).
> Utiliser exclusivement ces valeurs dans les composants Vue et le fichier `tailwind.config.js`.

---

## Couleurs de marque

| Rôle | Nom | Hex | HTML / Tailwind | Usage |
|---|---|---|---|---|
| Primaire | Emerald 500 | `#10B981` | `emerald-500` | CTA, liens actifs, icône principale |
| Primaire sombre | Emerald 700 | `#047857` | `emerald-700` | Hover sur boutons primaires |
| Primaire clair | Emerald 300 | `#6EE7B7` | `emerald-300` | Focus ring, highlights |
| Primaire très clair | Emerald 100 | `#D1FAE5` | `emerald-100` | Fond de badges "Confirmé" |
| Primaire profond | Emerald 900 | `#064E3B` | `emerald-900` | Texte sur fond clair très contrasté |

### Dégradé logo

```css
/* Bulle principale */
background: linear-gradient(135deg, #34D399 0%, #059669 100%);

/* Tailwind */
from-emerald-400 to-emerald-600
```

---

## Couleurs neutres (UI)

Basées sur **Slate** — palette froide, professionnelle, idéale pour un dashboard SaaS.

| Rôle | Nom | Hex | HTML / Tailwind |
|---|---|---|---|
| Fond page | Slate 50 | `#F8FAFC` | `slate-50` |
| Fond surface (cards) | Blanc | `#FFFFFF` | `white` |
| Fond surface hover | Slate 100 | `#F1F5F9` | `slate-100` |
| Bordure légère | Slate 200 | `#E2E8F0` | `slate-200` |
| Bordure marquée | Slate 300 | `#CBD5E1` | `slate-300` |
| Texte désactivé | Slate 400 | `#94A3B8` | `slate-400` |
| Texte secondaire / muted | Slate 500 | `#64748B` | `slate-500` |
| Texte secondaire fort | Slate 600 | `#475569` | `slate-600` |
| Texte corps | Slate 700 | `#334155` | `slate-700` |
| Texte principal | Slate 800 | `#1E293B` | `slate-800` |
| Texte titre / bold | Slate 900 | `#0F172A` | `slate-900` |

---

## Couleurs sémantiques

### Succès

| Rôle | Hex | Tailwind |
|---|---|---|
| Fond | `#D1FAE5` | `emerald-100` |
| Texte / icône | `#065F46` | `emerald-900` |
| Bordure | `#6EE7B7` | `emerald-300` |

### Avertissement

| Rôle | Hex | Tailwind |
|---|---|---|
| Fond | `#FEF3C7` | `amber-100` |
| Texte / icône | `#92400E` | `amber-800` |
| Bordure | `#FCD34D` | `amber-300` |

### Erreur / Danger

| Rôle | Hex | Tailwind |
|---|---|---|
| Fond | `#FEE2E2` | `red-100` |
| Texte / icône | `#991B1B` | `red-800` |
| Bordure | `#FCA5A5` | `red-300` |

### Info

| Rôle | Hex | Tailwind |
|---|---|---|
| Fond | `#DBEAFE` | `blue-100` |
| Texte / icône | `#1E40AF` | `blue-800` |
| Bordure | `#93C5FD` | `blue-300` |

---

## Statuts de réservation

Ces couleurs sont utilisées dans les badges `StatusBadge.vue` et la barre de stats `StatsBar.vue`.

| Statut | Label FR | Fond | Texte | Tailwind fond | Tailwind texte |
|---|---|---|---|---|---|
| `pending_verification` | À vérifier | `#F1F5F9` | `#475569` | `slate-100` | `slate-600` |
| `pending_reminder` | Rappel prévu | `#DBEAFE` | `#1E40AF` | `blue-100` | `blue-800` |
| `confirmed` | Confirmé | `#D1FAE5` | `#065F46` | `emerald-100` | `emerald-900` |
| `cancelled_by_client` | Annulé | `#FEF3C7` | `#92400E` | `amber-100` | `amber-800` |
| `cancelled_no_confirmation` | Pas de réponse | `#FEE2E2` | `#991B1B` | `red-100` | `red-800` |
| `no_show` | No-show | `#FEE2E2` | `#991B1B` | `red-100` | `red-800` |
| `show` | Présent | `#D1FAE5` | `#065F46` | `emerald-100` | `emerald-900` |

---

## Score de fiabilité

Couleurs des badges `ReliabilityBadge.vue`.

| Tier | Label | Seuil | Fond | Texte | Tailwind fond | Tailwind texte |
|---|---|---|---|---|---|---|
| `reliable` | Fiable | ≥ 90% | `#D1FAE5` | `#065F46` | `emerald-100` | `emerald-900` |
| `average` | Moyen | 70–89% | `#FEF3C7` | `#92400E` | `amber-100` | `amber-800` |
| `at_risk` | À risque | < 70% | `#FEE2E2` | `#991B1B` | `red-100` | `red-800` |
| `null` | Inconnu | — | `#F1F5F9` | `#475569` | `slate-100` | `slate-600` |

---

## Mode sombre (dark mode)

Non prévu pour le MVP. La palette Slate se prête bien à une inversion future :
- Fonds `slate-50` / `white` → `slate-900` / `slate-800`
- Textes `slate-900` → `slate-50`
- La couleur primaire Emerald reste identique dans les deux modes

---

## Configuration Tailwind

```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        brand: {
          50:  '#ECFDF5',
          100: '#D1FAE5',
          200: '#A7F3D0',
          300: '#6EE7B7',
          400: '#34D399',
          500: '#10B981', // primaire
          600: '#059669',
          700: '#047857',
          800: '#065F46',
          900: '#064E3B',
        },
      },
    },
  },
}
```

---

## Règles d'utilisation

- **Ne jamais utiliser** de couleur hors palette sans validation.
- **Contraste minimum** : ratio 4.5:1 pour le texte normal (WCAG AA).
- **Fond blanc uniquement** pour les cards et modales — pas de fond `slate-50` à l'intérieur d'un `slate-50`.
- **Un seul élément primaire** (emerald) par vue — les CTA se lisent mieux quand ils ne se disputent pas l'attention.
- **Les rouges sont réservés** aux états d'erreur, no-show, et at-risk — ne pas utiliser pour autre chose.
