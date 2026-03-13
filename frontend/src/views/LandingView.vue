<template>
  <div class="min-h-screen bg-white font-sans text-slate-900 antialiased">
    <nav class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur">
      <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <a href="/" class="flex items-center gap-2.5" aria-label="ZeroNoShow">
          <svg viewBox="0 0 80 84" class="h-8 w-8">
            <defs>
              <linearGradient id="landing-logo-gradient" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#34D399" />
                <stop offset="100%" stop-color="#059669" />
              </linearGradient>
            </defs>
            <path
              d="M 40 5 C 20.7 5 5 20.7 5 40 C 5 49.7 9 58.5 15.4 64.8 L 11.9 75.4 C 11.4 76.9 12.8 78.3 14.3 77.8 L 24.9 74.3 C 29.5 76.2 34.6 77.3 40 77.3 C 59.3 77.3 75 61.6 75 42.3 C 75 23 59.3 5 40 5 Z"
              fill="url(#landing-logo-gradient)"
            />
            <path
              d="M 24 42 L 33 51 L 56 28"
              fill="none"
              stroke="#fff"
              stroke-width="7"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          <span class="text-lg font-extrabold text-slate-900">
            Zero<span class="font-normal text-slate-500">NoShow</span>
          </span>
        </a>

        <div class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
          <a href="#comment" class="transition-colors hover:text-slate-900">Comment ça marche</a>
          <a href="#fonctionnalites" class="transition-colors hover:text-slate-900">
            Fonctionnalités
          </a>
          <a href="#tarifs" class="transition-colors hover:text-slate-900">Tarifs</a>
        </div>

        <div class="flex items-center gap-3">
          <a href="/login" class="hidden text-sm font-semibold text-slate-600 hover:text-slate-900 sm:inline">
            Connexion
          </a>
          <a
            href="/register"
            class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-600"
          >
            Essai gratuit 14j
          </a>
        </div>
      </div>
    </nav>

    <section class="gradient-hero grid-bg overflow-hidden pt-20 pb-24">
      <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid items-center gap-16 lg:grid-cols-2">
          <div>
            <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5">
              <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500" />
              <span class="text-xs font-semibold uppercase tracking-wide text-emerald-800">
                Zéro frais cachés · SMS à la carte
              </span>
            </div>
            <h1 class="mb-6 text-4xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-5xl">
              Les clients
              <br />
              <span class="gradient-text">au rendez-vous.</span>
            </h1>
            <p class="mb-8 max-w-md text-lg leading-relaxed text-slate-600">
              ZeroNoShow envoie automatiquement des SMS de confirmation à vos clients et détecte
              les défaillances grâce au
              <strong class="text-slate-800">score de fiabilité</strong>.
              Sans caution. Sans friction.
            </p>
            <div class="mb-10 flex flex-wrap gap-3">
              <a
                href="/register"
                class="rounded-xl bg-emerald-500 px-6 py-3 font-semibold text-white shadow-lg shadow-emerald-500/20 transition-colors hover:bg-emerald-600"
              >
                Démarrer gratuitement
              </a>
              <a
                href="#comment"
                class="rounded-xl border border-slate-200 bg-white px-6 py-3 font-semibold text-slate-700 transition-colors hover:border-slate-300"
              >
                Voir comment ça marche →
              </a>
            </div>
            <div class="flex items-center gap-4 text-sm text-slate-500">
              <div class="flex -space-x-2">
                <div
                  class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-slate-200 text-xs font-bold text-slate-600"
                >
                  M
                </div>
                <div
                  class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-emerald-200 text-xs font-bold text-emerald-700"
                >
                  S
                </div>
                <div
                  class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-blue-200 text-xs font-bold text-blue-700"
                >
                  D
                </div>
              </div>
              <span>
                Déjà utilisé par
                <strong class="text-slate-700">47 professionnels</strong>
                en France
              </span>
            </div>
          </div>

          <div class="relative flex justify-center">
            <div class="relative w-72 rounded-[40px] bg-slate-900 p-3 shadow-2xl">
              <div class="overflow-hidden rounded-[32px] bg-white">
                <div class="flex justify-between bg-slate-50 px-5 pt-3 pb-2 text-[11px] font-medium text-slate-500">
                  <span>9:41</span>
                  <span>Messages</span>
                  <span>●●●</span>
                </div>
                <div class="flex items-center gap-3 border-b border-slate-100 bg-white px-4 py-3">
                  <div class="leo-gradient flex h-9 w-9 items-center justify-center rounded-full">
                    <svg viewBox="0 0 80 84" class="h-5 w-5">
                      <path
                        d="M 40 5 C 20.7 5 5 20.7 5 40 C 5 49.7 9 58.5 15.4 64.8 L 11.9 75.4 C 11.4 76.9 12.8 78.3 14.3 77.8 L 24.9 74.3 C 29.5 76.2 34.6 77.3 40 77.3 C 59.3 77.3 75 61.6 75 42.3 C 75 23 59.3 5 40 5 Z"
                        fill="#fff"
                      />
                      <path
                        d="M 24 42 L 33 51 L 56 28"
                        fill="none"
                        stroke="#10B981"
                        stroke-width="7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-slate-900">ZeroNoShow</p>
                    <p class="text-[10px] text-slate-400">Service de confirmation</p>
                  </div>
                </div>
                <div class="space-y-3 bg-slate-50 px-4 py-4">
                  <div class="max-w-[85%]">
                    <div class="sms-bubble border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
                      <p class="text-xs leading-relaxed text-slate-700">
                        Bonjour Marc, confirmez votre table pour
                        <strong>4 pers. vendredi à 20h</strong>
                        chez La Bonne Table.
                      </p>
                      <p class="mt-1.5 text-[10px] font-medium text-emerald-600">
                        → Confirmer mon RDV
                      </p>
                    </div>
                    <p class="mt-1 ml-1 text-[10px] text-slate-400">ZeroNoShow · 14h03</p>
                  </div>

                  <div class="flex justify-end">
                    <div class="max-w-[75%]">
                      <div class="sms-bubble-right bg-emerald-500 px-3 py-2.5">
                        <p class="text-xs font-medium text-white">✓ Confirmé !</p>
                      </div>
                      <p class="mt-1 mr-1 text-right text-[10px] text-slate-400">Marc · 14h07</p>
                    </div>
                  </div>

                  <div class="flex justify-center">
                    <span
                      class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-semibold text-emerald-800"
                    >
                      <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2.5"
                          d="M5 13l4 4L19 7"
                        />
                      </svg>
                      Réservation confirmée automatiquement
                    </span>
                  </div>

                  <div class="max-w-[85%] pt-2">
                    <div class="sms-bubble border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
                      <p class="text-xs leading-relaxed text-slate-700">
                        ⚠️ Rappel : votre RDV est dans
                        <strong>30 min</strong>.
                        Confirmez ou annulez.
                      </p>
                      <p class="mt-1.5 text-[10px] font-medium text-red-500">
                        Score fiabilité : À risque 58%
                      </p>
                    </div>
                    <p class="mt-1 ml-1 text-[10px] text-slate-400">ZeroNoShow · 19h30</p>
                  </div>
                </div>
              </div>
            </div>

            <div
              class="absolute -right-2 top-16 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-lg sm:-right-4"
            >
              <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                No-shows ce mois
              </p>
              <div class="flex items-end gap-2">
                <span class="text-2xl font-extrabold text-slate-900">−68%</span>
                <span class="mb-0.5 text-xs font-semibold text-emerald-600">↓</span>
              </div>
            </div>

            <div
              class="absolute -left-2 bottom-24 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-lg sm:-left-4"
            >
              <div class="flex items-center gap-2">
                <div class="h-2 w-2 rounded-full bg-emerald-400" />
                <span class="text-[10px] font-semibold text-emerald-800">Fiable · 94%</span>
              </div>
              <p class="mt-0.5 text-[10px] text-slate-500">3 RDV · 0 no-show</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="border-y border-slate-200 bg-white">
      <div class="mx-auto grid max-w-6xl gap-6 px-4 py-10 sm:grid-cols-3 sm:divide-x sm:divide-slate-200 sm:px-6">
        <div class="px-4 text-center">
          <p class="mb-1 text-4xl font-extrabold text-slate-900">−68%</p>
          <p class="text-sm font-medium text-slate-500">de no-shows en moyenne</p>
        </div>
        <div class="px-4 text-center">
          <p class="mb-1 text-4xl font-extrabold text-emerald-500">3–4</p>
          <p class="text-sm font-medium text-slate-500">
            no-shows évités = abonnement rentabilisé
          </p>
        </div>
        <div class="px-4 text-center">
          <p class="mb-1 text-4xl font-extrabold text-slate-900">&lt; 5 min</p>
          <p class="text-sm font-medium text-slate-500">pour démarrer sans changer vos outils</p>
        </div>
      </div>
    </section>

    <section id="comment" class="bg-white py-24">
      <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mb-16 text-center">
          <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-emerald-600">
            Simple par design
          </p>
          <h2 class="text-3xl font-bold text-slate-900">Comment ça marche</h2>
          <p class="mx-auto mt-3 max-w-md text-slate-500">
            Branchez ZeroNoShow sur vos réservations existantes. Aucune migration. Aucun
            changement de workflow.
          </p>
        </div>

        <div class="grid gap-8 md:grid-cols-3">
          <article class="relative">
            <div class="mb-4 flex items-center gap-4">
              <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-lg font-bold text-white shadow-md shadow-emerald-500/25"
              >
                1
              </div>
              <div class="h-px flex-1 bg-slate-200" />
            </div>
            <h3 class="mb-2 text-lg font-semibold text-slate-900">Créez la réservation</h3>
            <p class="text-sm leading-relaxed text-slate-500">
              Saisissez le nom, le téléphone et la date. En 30 secondes, sans quitter votre carnet
              de réservations habituel.
            </p>
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
              <div class="flex items-center gap-2 text-xs text-slate-600">
                <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Numéro confirmé par téléphone → SMS ignoré
              </div>
            </div>
          </article>

          <article class="relative">
            <div class="mb-4 flex items-center gap-4">
              <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-lg font-bold text-white shadow-md shadow-emerald-500/25"
              >
                2
              </div>
              <div class="h-px flex-1 bg-slate-200" />
            </div>
            <h3 class="mb-2 text-lg font-semibold text-slate-900">Le SMS part automatiquement</h3>
            <p class="text-sm leading-relaxed text-slate-500">
              Votre client reçoit un SMS avec un lien de confirmation. Un tap suffit. Aucune app,
              aucun compte.
            </p>
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
              <div class="space-y-1.5">
                <div class="flex items-center gap-2 text-xs text-slate-600">
                  <span class="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                  Score &gt; 90% → aucun rappel
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-600">
                  <span class="h-1.5 w-1.5 rounded-full bg-amber-400" />
                  Score 70–89% → rappel 2h avant
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-600">
                  <span class="h-1.5 w-1.5 rounded-full bg-red-400" />
                  Score &lt; 70% → rappels 2h + 30 min
                </div>
              </div>
            </div>
          </article>

          <article>
            <div class="mb-4 flex items-center gap-4">
              <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-lg font-bold text-white shadow-md shadow-emerald-500/25"
              >
                3
              </div>
            </div>
            <h3 class="mb-2 text-lg font-semibold text-slate-900">Vous savez à l’avance</h3>
            <p class="text-sm leading-relaxed text-slate-500">
              Le tableau de bord affiche en temps réel qui est confirmé, qui ne l’est pas. Les
              créneaux libérés sont immédiatement disponibles.
            </p>
            <div class="mt-4 space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-600">Marc Dubois · 20h00</span>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-semibold text-emerald-800">
                  Confirmé
                </span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-600">Claire Martin · 20h30</span>
                <span class="rounded-full bg-red-100 px-2 py-0.5 font-semibold text-red-800">
                  No-show évité
                </span>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section id="fonctionnalites" class="overflow-hidden bg-slate-900 py-24">
      <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid items-center gap-16 lg:grid-cols-2">
          <div>
            <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-emerald-400">
              Différenciant · Unique
            </p>
            <h2 class="mb-4 text-3xl font-bold text-white">
              Le score de fiabilité partagé entre tous vos collègues
            </h2>
            <p class="mb-6 leading-relaxed text-slate-400">
              Un client qui fait un no-show chez le médecin en fera un au restaurant. Le score de
              fiabilité ZeroNoShow se construit sur l’ensemble de la plateforme — chaque
              professionnel bénéficie de l’expérience de tous les autres.
            </p>
            <div class="space-y-3">
              <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-emerald-500">
                  <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <p class="text-sm text-slate-300">
                  Connaissez la fiabilité d’un client avant même sa première réservation chez vous
                </p>
              </div>
              <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-emerald-500">
                  <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <p class="text-sm text-slate-300">
                  Le score s’améliore automatiquement — plus la plateforme grossit, plus il est
                  précis
                </p>
              </div>
              <div class="flex items-start gap-3">
                <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-emerald-500">
                  <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <p class="text-sm text-slate-300">
                  Ajustez l’intensité des rappels selon le risque réel, sans harceler les clients
                  fiables
                </p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <article class="col-span-2 rounded-2xl border border-slate-700 bg-slate-800 p-5">
              <p class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                Profil client — Marc Dubois
              </p>
              <div class="mb-3 flex items-center justify-between">
                <span class="text-2xl font-extrabold text-white">
                  94<span class="text-lg text-emerald-400">%</span>
                </span>
                <span class="rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-300">
                  Fiable
                </span>
              </div>
              <div class="h-2 w-full rounded-full bg-slate-700">
                <div class="h-2 w-[94%] rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600" />
              </div>
              <div class="mt-2 flex justify-between text-[10px] text-slate-500">
                <span>17 réservations · 16 présences</span>
                <span>0 annulation</span>
              </div>
            </article>

            <article class="rounded-2xl border border-slate-700 bg-slate-800 p-4">
              <p class="mb-2 text-[10px] font-semibold text-slate-400">À risque</p>
              <p class="text-xl font-extrabold text-red-400">58%</p>
              <div class="mt-2 h-1.5 w-full rounded-full bg-slate-700">
                <div class="h-1.5 w-[58%] rounded-full bg-red-500" />
              </div>
              <p class="mt-2 text-[10px] text-slate-500">Rappel 2h + 30 min recommandé</p>
            </article>

            <article class="rounded-2xl border border-slate-700 bg-slate-800 p-4">
              <p class="mb-2 text-[10px] font-semibold text-slate-400">Moyen</p>
              <p class="text-xl font-extrabold text-amber-400">76%</p>
              <div class="mt-2 h-1.5 w-full rounded-full bg-slate-700">
                <div class="h-1.5 w-[76%] rounded-full bg-amber-400" />
              </div>
              <p class="mt-2 text-[10px] text-slate-500">Rappel 2h uniquement</p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section id="tarifs" class="bg-white py-24">
      <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
          <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-emerald-600">
            Tarification simple
          </p>
          <h2 class="text-3xl font-bold text-slate-900">Un abonnement clair, rentable dès les premiers services</h2>
          <p class="mt-3 text-slate-500">
            Pas de commission sur les réservations. Pas de frais cachés. Vous ne payez que votre
            plan et les SMS réellement envoyés.
          </p>
        </div>

        <div class="mt-12 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
          <article class="rounded-[32px] border border-emerald-500 bg-white p-8 shadow-xl shadow-emerald-500/10">
            <div class="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Plan principal</p>
                <h3 class="mt-2 text-2xl font-bold text-slate-900">ZeroNoShow</h3>
                <p class="mt-3 max-w-xl text-slate-500">
                  Confirmation SMS automatique, score de fiabilité, rappels intelligents et
                  dashboard en temps réel pour piloter vos services.
                </p>
              </div>
              <div class="text-right">
                <p class="text-4xl font-extrabold text-slate-900">19€<span class="text-lg font-semibold text-slate-500">/mois</span></p>
                <p class="mt-2 text-sm text-slate-500">SMS facturés à l’usage</p>
              </div>
            </div>

            <div class="mt-8 grid gap-3 sm:grid-cols-2">
              <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">Confirmation automatique des réservations</div>
              <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">Rappels ciblés selon le score client</div>
              <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">Score de fiabilité partagé</div>
              <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">Dashboard jour / semaine</div>
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
              <a
                href="/register"
                class="rounded-xl bg-emerald-500 px-6 py-3 font-semibold text-white transition-colors hover:bg-emerald-600"
              >
                Démarrer l’essai gratuit
              </a>
              <a
                href="#comment"
                class="rounded-xl border border-slate-200 px-6 py-3 font-semibold text-slate-700 transition-colors hover:border-slate-300"
              >
                Voir le fonctionnement
              </a>
            </div>
          </article>

          <article class="rounded-[32px] border border-slate-200 bg-emerald-50 p-8">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Add-on optionnel</p>
                <h3 class="mt-2 text-2xl font-bold text-slate-900">Léo — Votre assistant Telegram</h3>
              </div>
              <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-800">
                9€/mois
              </span>
            </div>
            <p class="mt-4 text-slate-600">
              Activez un copilote conversationnel pour suivre votre journée sur Telegram sans ouvrir
              le dashboard.
            </p>
            <div class="mt-6 space-y-3">
              <div class="rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm text-slate-700">
                Résumé du jour en conversation naturelle
              </div>
              <div class="rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm text-slate-700">
                Liste des clients en attente et des prochains services
              </div>
              <div class="rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm text-slate-700">
                Notifications d’annulation et de no-show
              </div>
            </div>
            <p class="mt-6 text-sm text-slate-500">
              Un seul canal par établissement pour le MVP. Changement de canal possible via
              suppression puis recréation.
            </p>
          </article>
        </div>
      </div>
    </section>

    <footer class="border-t border-slate-200 bg-slate-50">
      <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
          <p class="text-lg font-extrabold text-slate-900">
            Zero<span class="font-normal text-slate-500">NoShow</span>
          </p>
          <p class="mt-2 max-w-md text-sm text-slate-500">
            Réduisez les no-shows, fluidifiez votre salle et gardez la maîtrise de vos réservations.
          </p>
        </div>
        <div class="flex flex-wrap gap-4 text-sm font-medium text-slate-600">
          <a href="#comment" class="hover:text-slate-900">Comment ça marche</a>
          <a href="#fonctionnalites" class="hover:text-slate-900">Fonctionnalités</a>
          <a href="#tarifs" class="hover:text-slate-900">Tarifs</a>
          <a href="/login" class="hover:text-slate-900">Connexion</a>
          <a href="/register" class="text-emerald-700 hover:text-emerald-900">Créer un compte</a>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.gradient-hero {
  background: linear-gradient(160deg, #f8fafc 0%, #ecfdf5 50%, #f8fafc 100%);
}

.gradient-text {
  background: linear-gradient(135deg, #10b981, #047857);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.grid-bg {
  background-image:
    linear-gradient(rgba(16, 185, 129, 0.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(16, 185, 129, 0.05) 1px, transparent 1px);
  background-size: 40px 40px;
}

.leo-gradient {
  background: linear-gradient(135deg, #34d399, #059669);
}

.sms-bubble {
  border-radius: 18px 18px 18px 4px;
}

.sms-bubble-right {
  border-radius: 18px 18px 4px 18px;
}
</style>
