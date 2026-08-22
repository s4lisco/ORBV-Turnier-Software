<div class="mx-auto max-w-2xl p-6">
    <a href="/organisatoren" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>
    <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Teilnehmer-Import</h1>

    <?php if ($error): ?>
        <p class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($result): ?>
        <p class="mb-4 rounded-lg bg-emerald-100 px-4 py-2 text-sm text-emerald-700">
            Import erfolgreich: <?= (int) $result['teilnehmer'] ?> Teilnehmer:innen in <?= (int) $result['kategorien'] ?> Kategorien.
        </p>
    <?php endif; ?>

    <form method="post" action="/admin/import" enctype="multipart/form-data" class="space-y-4 rounded-2xl bg-white p-6 shadow">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">CSV-Datei von turnier.orbv.ch</label>
            <input type="file" name="csv" accept=".csv" required class="w-full text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
            Hochladen &amp; importieren
        </button>
    </form>
</div>
