<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZeroNoShow - Résultat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-white font-[Inter] text-slate-900">
<main class="mx-auto flex min-h-screen max-w-xl items-center px-6 py-12">
    <section class="w-full rounded-3xl border border-slate-200 bg-gradient-to-br from-white to-emerald-50 p-8 shadow-sm">
        <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.25em] text-emerald-700">ZeroNoShow</p>
        <h1 class="mb-3 text-3xl font-extrabold">{{ $title }}</h1>
        <p class="text-sm leading-6 text-slate-600">{{ $message }}</p>
    </section>
</main>
</body>
</html>
