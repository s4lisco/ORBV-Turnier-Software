<div class="mx-auto max-w-2xl p-6">
    <a href="/admin/runden/<?= (int) $runde['id'] ?>" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Rundensteuerung</a>
    <h1 class="mb-1 mt-2 text-2xl font-bold text-slate-800">Zwischenrangliste: <?= htmlspecialchars($runde['kategorie_name']) ?></h1>
    <p class="mb-6 text-sm text-slate-500">
        Runde: <?= htmlspecialchars($runde['typ']) ?> – vorläufig, basierend auf den bisher abgegebenen Bewertungen.
        Keine offizielle Rangliste.
    </p>

    <?php if (empty($rangliste)): ?>
        <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">Noch keine Bewertungen vorhanden.</p>
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
        <table class="w-full rounded-2xl bg-white text-left text-sm shadow">
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
                <tr class="border-b">
                    <td class="px-4 py-3 font-semibold"><?= $eintrag['rang'] ?></td>
                    <td class="px-4 py-3">#<?= $t ? (int) $t['startnummer'] : '?' ?></td>
                    <td class="px-4 py-3"><?= $t ? htmlspecialchars($t['name']) . ($t['name_p2'] ? ' &amp; ' . htmlspecialchars($t['name_p2']) : '') : '?' ?></td>
                    <td class="px-4 py-3"><?= $t && $t['verein_name'] ? htmlspecialchars($t['verein_name']) : '–' ?></td>
                    <?php foreach ($kriteriumsNamen as $name): ?>
                        <td class="px-4 py-3"><?= $eintrag['kriterien'][$name] ?? '–' ?></td>
                    <?php endforeach; ?>
                    <td class="px-4 py-3 font-semibold"><?= $eintrag['gesamt'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
