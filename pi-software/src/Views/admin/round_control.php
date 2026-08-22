<div class="mx-auto max-w-2xl p-6">
    <a href="/admin/runden" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Übersicht</a>
    <h1 class="mb-1 mt-2 text-2xl font-bold text-slate-800"><?= htmlspecialchars($runde['kategorie_name']) ?></h1>
    <p class="mb-6 text-sm text-slate-500">Runde: <?= htmlspecialchars($runde['typ']) ?> · Status: <?= htmlspecialchars($runde['status']) ?></p>

    <div class="mb-6 rounded-2xl bg-white p-6 shadow">
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Nächste Startnummer(n) freigeben</h2>
        <?php if (empty($offene)): ?>
            <p class="text-sm text-slate-500">Alle Teilnehmer:innen dieser Runde wurden bereits freigegeben.</p>
        <?php else: ?>
            <form method="post" action="/admin/runden/<?= (int) $runde['id'] ?>/freigeben" class="space-y-2">
                <?php foreach ($offene as $t): ?>
                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                        <input type="checkbox" name="teilnehmer_ids[]" value="<?= (int) $t['id'] ?>" class="h-5 w-5">
                        <span>#<?= (int) $t['startnummer'] ?> – <?= htmlspecialchars($t['name']) ?><?= $t['name_p2'] ? ' &amp; ' . htmlspecialchars($t['name_p2']) : '' ?></span>
                    </label>
                <?php endforeach; ?>
                <button type="submit" class="mt-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Auswahl freigeben
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="mb-6 rounded-2xl bg-white p-6 shadow">
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Live-Status</h2>
        <?php if (empty($live)): ?>
            <p class="text-sm text-slate-500">Noch keine Startnummer freigegeben.</p>
        <?php else: ?>
            <table class="w-full text-left text-sm">
                <thead>
                <tr class="border-b text-slate-500">
                    <th class="py-2">Startnummer</th>
                    <th class="py-2">Name</th>
                    <th class="py-2">Richter fertig</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($live as $t): ?>
                    <tr class="border-b">
                        <td class="py-2">#<?= (int) $t['startnummer'] ?></td>
                        <td class="py-2"><?= htmlspecialchars($t['name']) ?><?= $t['name_p2'] ? ' &amp; ' . htmlspecialchars($t['name_p2']) : '' ?></td>
                        <td class="py-2"><?= (int) $t['anzahl_richter_fertig'] ?> / 5</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <form method="post" action="/admin/runden/<?= (int) $runde['id'] ?>/abschliessen">
        <button type="submit" <?= $kannAbschliessen ? '' : 'disabled' ?>
                class="w-full rounded-lg px-4 py-3 font-semibold text-white <?= $kannAbschliessen ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-300' ?>">
            Runde abschliessen<?= $kannAbschliessen ? '' : ' (noch nicht alle bewertet)' ?>
        </button>
    </form>
</div>
