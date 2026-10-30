<div class="mx-auto max-w-2xl p-6">
    <a href="/organisatoren" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>
    <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Fehlende Startnummern</h1>

    <?php if (empty($teilnehmer)): ?>
        <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">Allen Teilnehmer:innen wurde bereits eine Startnummer zugewiesen.</p>
    <?php else: ?>
        <table class="w-full rounded-2xl bg-white text-left text-sm shadow">
            <thead>
            <tr class="border-b text-slate-500">
                <th class="px-4 py-3">Kategorie</th>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Verein</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($teilnehmer as $t): ?>
                <tr class="border-b">
                    <td class="px-4 py-3"><?= htmlspecialchars($t['kategorie_name']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($t['name']) ?><?= $t['name_p2'] ? ' &amp; ' . htmlspecialchars($t['name_p2']) : '' ?></td>
                    <td class="px-4 py-3"><?= !empty($t['verein_name']) ? htmlspecialchars($t['verein_name']) : '–' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
