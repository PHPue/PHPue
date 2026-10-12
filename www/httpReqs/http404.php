<!-- Rendered inside App.pvue's layout in place of <View/> (Navbar and Footer stay). Plain PHP/HTML. -->
<main class="relative flex min-h-[65vh] items-center justify-center overflow-hidden px-4 py-16">
    <div class="pointer-events-none absolute -top-24 left-1/2 -z-10 h-72 w-[40rem] -translate-x-1/2 rounded-full bg-gradient-to-r from-ue-blue/20 via-ue-green/20 to-ue-amber/20 blur-3xl"></div>
    <div class="max-w-md text-center">
        <p class="bg-gradient-to-r from-ue-blue via-ue-green to-ue-amber bg-clip-text font-mono text-8xl font-extrabold text-transparent">404</p>
        <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900"><?= htmlspecialchars(Lang::t('e404_title')) ?></h1>
        <p class="mt-2 text-slate-600">
            <?= htmlspecialchars(Lang::t('e404_text')) ?>
            <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-sm text-slate-800"><?= htmlspecialchars(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/') ?></code>.
        </p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="/" class="rounded-full bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"><?= htmlspecialchars(Lang::t('e404_home')) ?></a>
            <button type="button" onclick="history.back()" class="rounded-full border border-slate-300 px-6 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400"><?= htmlspecialchars(Lang::t('e404_back')) ?></button>
        </div>
    </div>
</main>
