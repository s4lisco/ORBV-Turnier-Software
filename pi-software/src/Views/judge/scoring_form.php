<div class="mx-auto max-w-xl p-6">
    <a href="/kampfrichter/bewertung" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Liste</a>

    <h1 class="mb-1 mt-2 text-2xl font-bold text-slate-800">
        #<?= (int) $teilnehmer['startnummer'] ?> – <?= htmlspecialchars($teilnehmer['name']) ?><?= $teilnehmer['name_p2'] ? ' &amp; ' . htmlspecialchars($teilnehmer['name_p2']) : '' ?>
    </h1>
    <p class="mb-6 text-sm text-slate-500"><?= htmlspecialchars($teilnehmer['kategorie_name']) ?></p>

    <?php if ($error): ?>
        <p class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($bereitsBewertet): ?>
        <p class="rounded-2xl bg-emerald-100 p-6 text-center font-medium text-emerald-700">
            Bewertung wurde bereits abgesendet und kann nicht mehr geändert werden.
        </p>
    <?php else: ?>
        <form method="post" action="/kampfrichter/bewertung/<?= (int) $rundeId ?>/<?= (int) $teilnehmer['id'] ?>" class="space-y-4 rounded-2xl bg-white p-6 shadow">
            <?php foreach ($kriterien as $kriterium): ?>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700"><?= htmlspecialchars($kriterium['name']) ?> (0–10)</label>
                    <input type="number" name="punkte[<?= (int) $kriterium['id'] ?>]" min="0" max="10" step="0.5" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-lg focus:border-emerald-500 focus:outline-none">
                </div>
            <?php endforeach; ?>
            <button type="submit"
                    class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-lg font-semibold text-white hover:bg-emerald-700">
                Bewertung absenden (endgültig)
            </button>
        </form>
    <?php endif; ?>
</div>
