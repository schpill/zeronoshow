# Mini‑PRD --- Léo

Assistant WhatsApp pour ZeroNoShow

Version: 0.1\
Statut: Feature V2

------------------------------------------------------------------------

# 1. Vision

**Léo** est un assistant conversationnel accessible via **WhatsApp** qui
permet aux professionnels d'interroger leur agenda de réservations et de
recevoir des alertes importantes sans ouvrir le dashboard.

Léo transforme ZeroNoShow en **assistant métier temps réel**.

------------------------------------------------------------------------

# 2. Objectifs

Objectifs principaux :

1.  Permettre au restaurateur de consulter l'état de ses réservations
    instantanément
2.  Envoyer des notifications critiques (annulations, no-show)
3.  Simplifier l'usage de ZeroNoShow sans ouvrir l'interface web

------------------------------------------------------------------------

# 3. Utilisateurs cibles

-   restaurateurs
-   coiffeurs
-   médecins
-   salons de beauté
-   services à rendez-vous

------------------------------------------------------------------------

# 4. Canaux

Version initiale :

**WhatsApp Business**

Versions futures :

-   Telegram
-   SMS fallback
-   Web chat
-   Voix

------------------------------------------------------------------------

# 5. Fonctionnalités MVP

## 5.1 Consultation du statut du jour

Exemple :

Marc → WhatsApp\
"Léo combien de confirmations aujourd'hui ?"

Réponse :

Aujourd'hui :

18 réservations\
14 confirmées\
3 en attente\
1 annulée

------------------------------------------------------------------------

## 5.2 Liste des clients non confirmés

Commande :

"Léo qui n'a pas confirmé ?"

Réponse :

Clients en attente :

19:30 --- Martin\
20:00 --- Dupont\
21:15 --- Leroy

------------------------------------------------------------------------

## 5.3 Notification d'annulation

⚠️ Annulation

Julie Martin\
19:30 --- 2 personnes

------------------------------------------------------------------------

## 5.4 Récapitulatif du service

Commande :

"Léo résumé ce soir"

Réponse :

Service du soir :

18 réservations\
14 confirmées\
3 en attente\
1 annulée\
Score moyen clients : 84%

------------------------------------------------------------------------

## 5.5 Liste des prochains clients

Commande :

"Léo prochains clients"

Réponse :

Prochaines réservations :

19:30 --- Martin (2)\
20:00 --- Dupont (4)\
20:30 --- Leroy (2)

------------------------------------------------------------------------

# 6. Fonctionnalités V2

-   modification réservation
-   création réservation
-   appel automatique client
-   waitlist intelligente

------------------------------------------------------------------------

# 7. Architecture technique

Flux :

WhatsApp\
↓\
Webhook WhatsApp\
↓\
LeoAgentController\
↓\
LeoService\
↓\
• reservations repository\
• business resolver\
• optional LLM

------------------------------------------------------------------------

## Exemple endpoint

POST /api/leo/message

Payload :

{ "phone": "+33612345678", "message": "combien de confirmations" }

------------------------------------------------------------------------

# 8. Identification du business

Léo identifie le professionnel via :

phone_number → business_id

Table :

business_whatsapp_numbers

------------------------------------------------------------------------

# 9. Notifications

Triggers backend :

-   client annule
-   no-show marqué
-   table libérée

Jobs :

SendLeoNotificationJob

------------------------------------------------------------------------

# 10. Pricing

Proposition :

Léo Assistant\
gratuit 3 mois\
puis 9€/mois

Alternative :

Premium plan\
29€/mois\
inclut Léo

------------------------------------------------------------------------

# 11. KPI

-   \% utilisateurs qui activent Léo
-   nombre de messages / jour
-   réduction no-show

------------------------------------------------------------------------

# Prompt système de Léo

You are Léo, the WhatsApp assistant for ZeroNoShow.

Your role is to help small businesses manage their reservations and
prevent no-shows.

You interact with business owners such as restaurant managers,
hairdressers, and doctors.

You must be:

• concise\
• helpful\
• friendly\
• professional

Never use long explanations.

Always return clear reservation summaries.

You have access to the following tools:

getTodayStats\
getPendingReservations\
getUpcomingReservations\
getCancelledReservations\
getReservationDetails

If the user asks about today's situation, call getTodayStats.

If the user asks about clients who did not confirm, call
getPendingReservations.

If the user asks about upcoming reservations, call
getUpcomingReservations.

Always format answers for mobile reading.

Example response:

Today:

18 reservations\
14 confirmed\
3 pending\
1 cancelled

If you don't understand the message, ask a short clarification question.

Never mention internal APIs or system architecture.

Never expose customer phone numbers.

Always speak in the user's language.

If the user writes in French, respond in French.

------------------------------------------------------------------------

# Commandes Léo

## résumé

résumé\
résumé aujourd'hui\
résumé ce soir

------------------------------------------------------------------------

## confirmations

combien de confirmations\
confirmations\
confirmés

------------------------------------------------------------------------

## en attente

non confirmés\
en attente\
qui n'a pas confirmé

------------------------------------------------------------------------

## prochains clients

prochains clients\
prochaines réservations\
qui arrive ensuite

------------------------------------------------------------------------

## annulations

annulations\
qui a annulé

------------------------------------------------------------------------

## détails

détails martin\
détails réservation 19h30

------------------------------------------------------------------------

# Commandes futures

## créer réservation

créer réservation

## modifier réservation

modifier réservation

## appeler client

appelle martin

------------------------------------------------------------------------

# Exemple conversation

Marc →\
Léo résumé ce soir

Léo →

Service du soir

18 réservations\
14 confirmées\
3 en attente\
1 annulée

------------------------------------------------------------------------

Marc →\
Léo qui n'a pas confirmé ?

Léo →

Clients en attente :

19:30 --- Martin\
20:00 --- Dupont\
21:15 --- Leroy
