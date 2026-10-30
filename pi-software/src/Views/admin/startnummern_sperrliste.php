<div class="mx-auto max-w-lg p-6">
    <a href="/organisatoren" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>
    <h1 class="mb-2 mt-2 text-2xl font-bold text-slate-800">Startnummern-Sperrliste</h1>
    <p class="mb-6 text-sm text-slate-500">
        Nummern auf dieser Liste werden bei "Startnummern vergeben" übersprungen – z.B. weil
        die physische Nummernkarte fehlt oder beschädigt ist.
    </p>

    <form method="post" action="/admin/startnummern/sperrliste" class="mb-6 flex items-end gap-3 rounded-2xl bg-white p-6 shadow">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Startnummer sperren</label>
            <input type="number" name="nummer" min="1" required
                   class="w-32 rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
        </div>
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Sperren
        </button>
    </form>

    <?php if (empty($gesperrt)): ?>
        <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">Keine Startnummern gesperrt.</p>
    <?php else: ?>
        <table class="w-full rounded-2xl bg-white text-left text-sm shadow">
            <thead>
            <tr class="border-b text-slate-500">
                <th class="px-4 py-3">Startnummer</th>
                <th class="px-4 py-3"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($gesperrt as $g): ?>
                <tr class="border-b">
                    <td class="px-4 py-3">#<?= (int) $g['nummer'] ?></td>
                    <td class="px-4 py-3 text-right">
                        <form method="post" action="/admin/startnummern/sperrliste/<?= (int) $g['nummer'] ?>/entfernen">
                            <button type="submit" class="text-xs text-red-500 hover:underline">entfernen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
