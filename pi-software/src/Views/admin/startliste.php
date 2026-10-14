<div class="mx-auto max-w-2xl p-6 print:max-w-full print:p-0">
    <div class="mb-4 flex items-center justify-between print:hidden">
        <a href="<?= htmlspecialchars($backUrl ?? '/admin/runden/' . (int) $runde['id']) ?>" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Rundensteuerung</a>
        <button type="button" onclick="window.print()" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Drucken
        </button>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow print:rounded-none print:p-0 print:shadow-none">
        <h1 class="text-2xl font-bold text-slate-800">Startliste: <?= htmlspecialchars($runde['kategorie_name']) ?></h1>
        <p class="mb-6 text-sm text-slate-500">
            Runde: <?= htmlspecialchars($runde['typ']) ?><?= $paarweise ? ' · Je zwei Startnummern tanzen gleichzeitig' : '' ?>
        </p>

        <?php if (empty($teilnehmer)): ?>
            <p class="text-sm text-slate-500">Keine Teilnehmer:innen in dieser Runde.</p>
        <?php elseif ($paarweise): ?>
            <table class="w-full text-left text-sm">
                <thead>
                <tr class="border-b text-slate-500">
                    <th class="py-2">Paar</th>
                    <th class="py-2">Startnummer &amp; Name</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($gruppen as $i => $gruppe): ?>
                    <tr class="border-b">
                        <td class="py-2 align-top font-semibold"><?= $i + 1 ?></td>
                        <td class="py-2">
                            <?php foreach ($gruppe as $t): ?>
                                <div>
                                    #<?= $t['startnummer'] !== null ? (int) $t['startnummer'] : '–' ?> – <?= htmlspecialchars($t['name']) ?><?= $t['name_p2'] ? ' &amp; ' . htmlspecialchars($t['name_p2']) : '' ?>
                                    <?= !empty($t['verein_name']) ? ' <span class="text-slate-500">(' . htmlspecialchars($t['verein_name']) . ')</span>' : '' ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table class="w-full text-left text-sm">
                <thead>
                <tr class="border-b text-slate-500">
                    <th class="py-2">#</th>
                    <th class="py-2">Startnummer</th>
                    <th class="py-2">Name</th>
                    <th class="py-2">Verein</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($teilnehmer as $i => $t): ?>
                    <tr class="border-b">
                        <td class="py-2"><?= $i + 1 ?></td>
                        <td class="py-2">#<?= $t['startnummer'] !== null ? (int) $t['startnummer'] : '–' ?></td>
                        <td class="py-2"><?= htmlspecialchars($t['name']) ?><?= $t['name_p2'] ? ' &amp; ' . htmlspecialchars($t['name_p2']) : '' ?></td>
                        <td class="py-2"><?= !empty($t['verein_name']) ? htmlspecialchars($t['verein_name']) : '–' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
