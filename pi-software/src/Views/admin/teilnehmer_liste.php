<div class="mx-auto max-w-2xl p-6">
    <a href="/admin/runden" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Übersicht</a>
    <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Teilnehmer:innen: <?= htmlspecialchars($kategorie['name']) ?></h1>

    <?php if (empty($teilnehmer)): ?>
        <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">Noch keine Teilnehmer:innen in dieser Kategorie.</p>
    <?php else: ?>
        <table class="w-full rounded-2xl bg-white text-left text-sm shadow">
            <thead>
            <tr class="border-b text-slate-500">
                <th class="px-4 py-3">Startnummer</th>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Verein</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($teilnehmer as $t): ?>
                <tr class="border-b">
                    <td class="px-4 py-3">#<?= $t['startnummer'] !== null ? (int) $t['startnummer'] : '–' ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($t['name']) ?><?= $t['name_p2'] ? ' &amp; ' . htmlspecialchars($t['name_p2']) : '' ?></td>
                    <td class="px-4 py-3"><?= !empty($t['verein_name']) ? htmlspecialchars($t['verein_name']) : '–' ?></td>
                    <td class="px-4 py-3"><?= $t['status'] === 'dnf' ? 'DNF' : 'aktiv' ?></td>
                    <td class="px-4 py-3 text-right">
                        <form method="post" action="/admin/teilnehmer/<?= (int) $t['id'] ?>/loeschen"
                              onsubmit="return confirm('<?= htmlspecialchars($t['name']) ?> wirklich endgültig löschen? Alle Bewertungen dieser Person gehen dabei verloren.')">
                            <button type="submit" class="text-xs text-red-500 hover:underline">löschen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
