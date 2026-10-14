<div class="mx-auto max-w-2xl p-6 print:max-w-full print:p-0">
    <div class="mb-1 flex items-center justify-between print:hidden">
        <a href="/admin/ergebnisse" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Übersicht</a>
        <?php if (!empty($rangliste)): ?>
            <button type="button" onclick="window.print()" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Drucken
            </button>
        <?php endif; ?>
    </div>
    <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Endrangliste: <?= htmlspecialchars($kategorie['name']) ?></h1>

    <?php if (empty($rangliste)): ?>
        <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">Noch keine Endrangliste verfügbar.</p>
    <?php else: ?>
        <?php
            $kriteriumsNamen = [];
            foreach ($rangliste as $eintrag) {
                if (!empty($eintrag['kriterien'])) {
                    $kriteriumsNamen = array_keys($eintrag['kriterien']);
                    break;
                }
            }
        ?>
        <table class="w-full rounded-2xl bg-white text-left text-sm shadow print:rounded-none print:shadow-none">
            <thead>
            <tr class="border-b text-slate-500">
                <th class="px-4 py-3">Rang</th>
                <th class="px-4 py-3">Startnummer</th>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Verein</th>
                <?php foreach ($kriteriumsNamen as $name): ?>
                    <th class="px-4 py-3"><?= htmlspecialchars($name) ?></th>
                <?php endforeach; ?>
                <th class="px-4 py-3">Gesamt</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rangliste as $eintrag): ?>
                <?php $t = $teilnehmerNamen[$eintrag['teilnehmer_id']] ?? null; ?>
                <tr class="border-b<?= $eintrag['rang'] === null ? ' text-slate-400' : '' ?>">
                    <td class="px-4 py-3 font-semibold"><?= $eintrag['rang'] === null ? 'DNF' : $eintrag['rang'] ?></td>
                    <td class="px-4 py-3">#<?= $t ? (int) $t['startnummer'] : '?' ?></td>
                    <td class="px-4 py-3"><?= $t ? htmlspecialchars($t['name']) . ($t['name_p2'] ? ' &amp; ' . htmlspecialchars($t['name_p2']) : '') : '?' ?></td>
                    <td class="px-4 py-3"><?= $t && $t['verein_name'] ? htmlspecialchars($t['verein_name']) : '–' ?></td>
                    <?php foreach ($kriteriumsNamen as $name): ?>
                        <td class="px-4 py-3"><?= $eintrag['kriterien'][$name] ?? '–' ?></td>
                    <?php endforeach; ?>
                    <td class="px-4 py-3 font-semibold"><?= $eintrag['gesamt'] ?? 'nicht getanzt' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
