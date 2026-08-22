<div class="mx-auto max-w-2xl p-6">
    <a href="/organisatoren" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>
    <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Kampfrichter-Codes</h1>

    <div class="rounded-2xl bg-white p-6 shadow">
        <?php if (empty($richter)): ?>
            <p class="mb-4 text-sm text-slate-500">Noch keine Codes erzeugt.</p>
        <?php else: ?>
            <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                <?php foreach ($richter as $i => $r): ?>
                    <div class="rounded-xl bg-slate-100 p-4 text-center">
                        <div class="text-xs text-slate-500">Richter <?= $i + 1 ?></div>
                        <div class="mt-1 text-2xl font-bold tracking-widest"><?= htmlspecialchars($r['turnier_code']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (count($richter) < $maxRichter): ?>
            <form method="post" action="/admin/judges/generate">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                    Fehlende Codes erzeugen (<?= count($richter) ?>/<?= $maxRichter ?>)
                </button>
            </form>
        <?php else: ?>
            <p class="text-sm text-emerald-700">Alle 5 Kampfrichter-Codes wurden erzeugt.</p>
        <?php endif; ?>
    </div>
</div>
