# Typographie — ZeroNoShow

> Police unique : **Inter** — conçue spécifiquement pour les interfaces écran, lisible à toutes les tailles, idéale pour les dashboards SaaS.
> Police monospace : **JetBrains Mono** — pour les aperçus SMS, codes, et données techniques.

---

## Chargement des polices

```html
<!-- Dans index.html ou le layout Blade -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
```

```css
/* tailwind.config.js — fontFamily */
fontFamily: {
  sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
  mono: ['JetBrains Mono', 'ui-monospace', 'monospace'],
}
```

---

## Échelle typographique

### Titres

| Balise | Taille | Rem | Graisse | Interligne | Tailwind | Usage |
|---|---|---|---|---|---|---|
| `h1` | 36px | 2.25rem | 800 | 1.2 | `text-4xl font-extrabold leading-tight` | Titre de page principale |
| `h2` | 30px | 1.875rem | 700 | 1.25 | `text-3xl font-bold leading-tight` | Section majeure |
| `h3` | 24px | 1.5rem | 700 | 1.3 | `text-2xl font-bold` | Sous-section, titre de card |
| `h4` | 20px | 1.25rem | 600 | 1.4 | `text-xl font-semibold` | En-tête de tableau, panel |
| `h5` | 16px | 1rem | 600 | 1.5 | `text-base font-semibold` | Label de groupe, section compacte |
| `h6` | 14px | 0.875rem | 600 | 1.5 | `text-sm font-semibold` | En-tête de colonne, mini-titre |

### Corps de texte

| Rôle | Taille | Rem | Graisse | Interligne | Tailwind | Usage |
|---|---|---|---|---|---|---|
| `body-lg` | 18px | 1.125rem | 400 | 1.6 | `text-lg font-normal leading-relaxed` | Texte marketing, onboarding |
| `body` | 16px | 1rem | 400 | 1.6 | `text-base font-normal leading-relaxed` | Texte principal, formulaires |
| `body-sm` | 14px | 0.875rem | 400 | 1.5 | `text-sm font-normal` | Notes, descriptions secondaires |
| `body-xs` | 12px | 0.75rem | 400 | 1.4 | `text-xs font-normal` | Mentions légales, tooltips |

### UI / Interface

| Rôle | Taille | Rem | Graisse | Tailwind | Usage |
|---|---|---|---|---|---|
| `label` | 14px | 0.875rem | 500 | `text-sm font-medium` | Labels de formulaire |
| `label-sm` | 12px | 0.75rem | 500 | `text-xs font-medium` | Labels compacts, tableaux |
| `caption` | 12px | 0.75rem | 400 | `text-xs font-normal` | Légendes, metadata (date, heure) |
| `overline` | 11px | 0.6875rem | 600 | `text-[11px] font-semibold tracking-widest uppercase` | Catégories, taglines (ex: "LES CLIENTS AU RENDEZ-VOUS") |
| `badge` | 12px | 0.75rem | 600 | `text-xs font-semibold` | Statuts, scores de fiabilité |
| `button-lg` | 16px | 1rem | 600 | `text-base font-semibold` | Bouton CTA principal |
| `button` | 14px | 0.875rem | 600 | `text-sm font-semibold` | Bouton standard |
| `button-sm` | 13px | 0.8125rem | 500 | `text-[13px] font-medium` | Bouton compact, action tableau |

### Monospace

| Rôle | Taille | Rem | Graisse | Tailwind | Usage |
|---|---|---|---|---|---|
| `code` | 14px | 0.875rem | 400 | `font-mono text-sm` | Aperçu SMS, numéros de téléphone, tokens |
| `code-sm` | 12px | 0.75rem | 400 | `font-mono text-xs` | Logs SMS, codes d'erreur |

---

## Hiérarchie visuelle — exemples d'application

### Page Dashboard

```
h1 (800, 36px)    →  "Réservations"
h6 (600, 14px)    →  "Jeudi 12 mars 2026"
body-sm (400, 14px) → "3 confirmées · 1 en attente · 0 no-show"

[Card réservation]
label (500, 14px)   →  "Marc Dubois"
caption (400, 12px) →  "20:00 · 2 couverts"
badge (600, 12px)   →  "Confirmé"
```

### Formulaire de réservation

```
h3 (700, 24px)    →  "Nouvelle réservation"
label (500, 14px) →  "Téléphone"
body (400, 16px)  →  "+33 6 12 34 56 78"  ← input
caption (400, 12px) → "Le SMS sera envoyé à ce numéro"
badge (600, 12px) →  "Fiable 94%"
```

### Badge de fiabilité

```
badge (600, 12px, emerald) → "Fiable 94%"
badge (600, 12px, amber)   → "Moyen 75%"
badge (600, 12px, red)     → "À risque 58%"
badge (600, 12px, slate)   → "Inconnu"
```

---

## Couleurs de texte associées

Référence croisée avec `colors.md` :

| Rôle typographique | Couleur | Hex | Tailwind |
|---|---|---|---|
| Titres principaux | Slate 900 | `#0F172A` | `text-slate-900` |
| Corps de texte | Slate 700 | `#334155` | `text-slate-700` |
| Texte secondaire | Slate 500 | `#64748B` | `text-slate-500` |
| Texte désactivé / placeholder | Slate 400 | `#94A3B8` | `text-slate-400` |
| Lien / texte primaire | Emerald 700 | `#047857` | `text-emerald-700` |
| Lien hover | Emerald 900 | `#064E3B` | `text-emerald-900` |
| Overline / tagline | Slate 500 | `#64748B` | `text-slate-500` |

---

## Classes CSS utilitaires recommandées

Ajouter dans `resources/css/app.css` :

```css
@layer components {
  .text-heading-1 {
    @apply text-4xl font-extrabold leading-tight tracking-tight text-slate-900;
  }
  .text-heading-2 {
    @apply text-3xl font-bold leading-tight tracking-tight text-slate-900;
  }
  .text-heading-3 {
    @apply text-2xl font-bold text-slate-900;
  }
  .text-heading-4 {
    @apply text-xl font-semibold text-slate-800;
  }
  .text-body {
    @apply text-base font-normal leading-relaxed text-slate-700;
  }
  .text-body-sm {
    @apply text-sm font-normal text-slate-600;
  }
  .text-label {
    @apply text-sm font-medium text-slate-700;
  }
  .text-caption {
    @apply text-xs font-normal text-slate-500;
  }
  .text-overline {
    @apply text-[11px] font-semibold uppercase tracking-widest text-slate-500;
  }
  .text-badge {
    @apply text-xs font-semibold;
  }
}
```

---

## Règles typographiques

- **Ne jamais descendre en dessous de 11px** — en dessous, l'accessibilité est compromise.
- **Interligne minimum 1.4** pour tout texte de plus d'une ligne.
- **Pas plus de 2 graisses différentes** sur une même vue — 400 + 600 ou 500 + 700.
- **Tracking (letter-spacing) uniquement** pour les `overline` et `badge` — jamais pour les titres ou le corps.
- **Inter 800 (extrabold)** est réservé au `h1` et au logo — ne pas l'utiliser ailleurs.
- **Monospace uniquement** pour les numéros de téléphone, aperçus SMS, et tokens techniques.
