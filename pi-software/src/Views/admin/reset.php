<div class="mx-auto max-w-lg p-6">
    <a href="/organisatoren" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>

    <div class="mt-4 rounded-2xl border-2 border-red-300 bg-white p-8 shadow-lg">
        <h1 class="mb-2 text-2xl font-bold text-red-700">Turnier zurücksetzen</h1>
        <p class="mb-6 text-sm text-slate-600">
            Löscht unwiderruflich <strong>alle</strong> Turnierdaten auf diesem Gerät: Kategorien,
            Teilnehmer:innen, Startnummern, Runden, Bewertungen und Wertungsrichter-Codes. Der
            Wertungsbüro-Login bleibt bestehen, danach kann direkt ein neues Turnier aufgesetzt
            werden. Bereits heruntergeladene Exporte bleiben davon unberührt.
        </p>

        <?php if ($error): ?>
            <p class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="/admin/reset" class="space-y-4"
              onsubmit="return confirm('Wirklich ALLE Turnierdaten unwiderruflich löschen?')">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Admin-Passwort</label>
                <input type="password" name="password" required autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-red-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">
                    Zur Bestätigung <code class="rounded bg-slate-100 px-1"><?= htmlspecialchars($bestaetigungstext) ?></code> eingeben
                </label>
                <input type="text" name="bestaetigung" required autocomplete="off"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-red-500 focus:outline-none">
            </div>
            <button type="submit"
                    class="w-full rounded-lg bg-red-600 px-4 py-3 font-semibold text-white hover:bg-red-700">
                Turnier endgültig zurücksetzen
            </button>
        </form>
    </div>
</div>
