<div class="mx-auto max-w-2xl p-6">
    <a href="/organisatoren" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>
    <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Ergebnisse</h1>

    <div class="space-y-3">
        <?php foreach ($kategorien as $kategorie): ?>
            <a href="/admin/ergebnisse/<?= (int) $kategorie['id'] ?>"
               class="flex items-center justify-between rounded-xl bg-white p-4 shadow hover:bg-slate-50">
                <span class="font-semibold"><?= htmlspecialchars($kategorie['name']) ?></span>
                <span class="text-blue-600">ansehen →</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
