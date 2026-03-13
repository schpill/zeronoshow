<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZeroNoShow - Confirmation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-50 font-[Inter] text-slate-900">
<main class="mx-auto flex min-h-screen max-w-xl items-center px-6 py-12">
    <section class="w-full rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.25em] text-emerald-700">ZeroNoShow</p>
        <h1 class="mb-3 text-3xl font-extrabold">Confirmez votre réservation</h1>
        <p class="mb-8 text-sm leading-6 text-slate-600">
            {{ $reservation->business->name }} vous attend le
            <strong>{{ $reservation->scheduled_at->timezone($reservation->business->timezone)->format('d/m/Y à H:i') }}</strong>
            pour <strong>{{ $reservation->guests }}</strong> couvert(s), au nom de {{ $reservation->customer_name }}.
        </p>

        <form action="{{ route('confirmation.confirm', $reservation->confirmation_token) }}" method="POST" class="space-y-3">
            @csrf
            <button type="submit" name="action" value="confirm" class="w-full rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-600">
                Confirmer ma venue
            </button>
            <button type="submit" name="action" value="cancel" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-red-300 hover:text-red-700">
                Annuler la réservation
            </button>
        </form>
    </section>
</main>
</body>
</html>
