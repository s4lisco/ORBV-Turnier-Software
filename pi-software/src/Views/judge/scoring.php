<div class="mx-auto max-w-xl p-6">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">Offene Bewertungen</h1>
        <form method="post" action="/kampfrichter/logout">
            <button type="submit" class="text-sm text-slate-500 hover:underline">Abmelden</button>
        </form>
    </div>

    <?php if (empty($offene)): ?>
        <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">
            Aktuell sind keine Startnummern zur Bewertung freigegeben.
        </p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($offene as $item): ?>
                <?php $bewertet = (int) $item['bereits_bewertet'] > 0; ?>
                <a href="/kampfrichter/bewertung/<?= (int) $item['runde_id'] ?>/<?= (int) $item['teilnehmer_id'] ?>"
                   class="flex items-center justify-between rounded-xl bg-white p-4 shadow <?= $bewertet ? 'opacity-50' : 'hover:bg-slate-50' ?>">
                    <div>
                        <div class="font-semibold">#<?= (int) $item['startnummer'] ?> – <?= htmlspecialchars($item['name']) ?><?= $item['name_p2'] ? ' &amp; ' . htmlspecialchars($item['name_p2']) : '' ?></div>
                        <div class="text-sm text-slate-500"><?= htmlspecialchars($item['kategorie_name']) ?> · <?= htmlspecialchars($item['runde_typ']) ?></div>
                    </div>
                    <span class="text-sm font-medium <?= $bewertet ? 'text-emerald-600' : 'text-blue-600' ?>">
                        <?= $bewertet ? 'abgesendet' : 'bewerten →' ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
