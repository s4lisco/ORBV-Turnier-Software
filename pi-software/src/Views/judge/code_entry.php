<div class="flex min-h-screen items-center justify-center p-6">
    <div class="w-full max-w-sm rounded-2xl bg-white p-8 text-center shadow-lg">
        <h1 class="mb-6 text-2xl font-bold text-slate-800">Wertungsrichter-Code</h1>

        <?php if ($error): ?>
            <p class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="/kampfrichter" class="space-y-4">
            <input type="text" name="code" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required autofocus
                   class="w-full rounded-lg border border-slate-300 px-3 py-4 text-center text-3xl tracking-[0.5em] focus:border-emerald-500 focus:outline-none"
                   placeholder="0000">
            <button type="submit"
                    class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-lg font-semibold text-white hover:bg-emerald-700">
                Anmelden
            </button>
        </form>
    </div>
</div>
