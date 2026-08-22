<div class="mx-auto max-w-3xl p-6">
    <a href="/organisatoren" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>
    <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Runden</h1>

    <div class="space-y-3">
        <?php foreach ($kategorien as $kategorie): ?>
            <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow">
                <div>
                    <div class="font-semibold"><?= htmlspecialchars($kategorie['name']) ?></div>
                    <div class="text-sm text-slate-500"><?= (int) $kategorie['anzahl_teilnehmer'] ?> Teilnehmer:innen</div>
                </div>
                <?php if ((int) $kategorie['anzahl_runden'] === 0): ?>
                    <form method="post" action="/admin/runden/<?= (int) $kategorie['id'] ?>/starten">
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Erste Runde starten
                        </button>
                    </form>
                <?php elseif ($kategorie['laufende_runde_id']): ?>
                    <a href="/admin/runden/<?= (int) $kategorie['laufende_runde_id'] ?>" class="text-sm font-semibold text-blue-600 hover:underline">Laufende Runde steuern →</a>
                <?php else: ?>
                    <a href="/admin/ergebnisse/<?= (int) $kategorie['id'] ?>" class="text-sm text-blue-600 hover:underline">Ergebnisse ansehen →</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
