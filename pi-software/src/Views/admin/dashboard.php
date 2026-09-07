<div class="mx-auto max-w-4xl p-6">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">Organisator-Dashboard</h1>
        <form method="post" action="/admin/logout">
            <button type="submit" class="text-sm text-slate-500 hover:underline">Abmelden</button>
        </form>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
        <a href="/admin/import" class="rounded-xl bg-white p-4 text-center shadow hover:bg-slate-50">
            <div class="text-2xl">📥</div>
            <div class="mt-1 text-sm font-medium">Import</div>
        </a>
        <a href="/admin/judges" class="rounded-xl bg-white p-4 text-center shadow hover:bg-slate-50">
            <div class="text-2xl">⭐</div>
            <div class="mt-1 text-sm font-medium">Wertungsrichter (<?= $richterAnzahl ?>/5)</div>
        </a>
        <a href="/admin/runden" class="rounded-xl bg-white p-4 text-center shadow hover:bg-slate-50">
            <div class="text-2xl">🏆</div>
            <div class="mt-1 text-sm font-medium">Runden</div>
        </a>
        <a href="/admin/rangliste-export" class="rounded-xl bg-white p-4 text-center shadow hover:bg-slate-50">
            <div class="text-2xl">🏁</div>
            <div class="mt-1 text-sm font-medium">Rangliste für turnier.orbv.ch</div>
        </a>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800">Kategorien</h2>
            <form method="post" action="/admin/startnummern">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Startnummern vergeben
                </button>
            </form>
        </div>

        <?php if (empty($kategorien)): ?>
            <p class="text-sm text-slate-500">Noch keine Kategorien importiert.</p>
        <?php else: ?>
            <table class="w-full text-left text-sm">
                <thead>
                <tr class="border-b text-slate-500">
                    <th class="py-2">Kategorie</th>
                    <th class="py-2">Typ</th>
                    <th class="py-2">Teilnehmer:innen</th>
                    <th class="py-2">Startnummern vergeben</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($kategorien as $kategorie): ?>
                    <tr class="border-b">
                        <td class="py-2"><?= htmlspecialchars($kategorie['name']) ?></td>
                        <td class="py-2"><?= htmlspecialchars($kategorie['typ']) ?></td>
                        <td class="py-2"><?= (int) $kategorie['anzahl_teilnehmer'] ?></td>
                        <td class="py-2"><?= (int) $kategorie['anzahl_mit_startnummer'] ?> / <?= (int) $kategorie['anzahl_teilnehmer'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="mt-6 text-center">
        <a href="/admin/reset" class="text-xs text-red-500 hover:underline">Turnier zurücksetzen…</a>
    </div>
</div>
