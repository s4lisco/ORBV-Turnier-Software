<div class="mx-auto max-w-2xl p-6">
    <a href="/admin/runden" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Übersicht</a>
    <h1 class="mb-1 mt-2 text-2xl font-bold text-slate-800"><?= htmlspecialchars($runde['kategorie_name']) ?></h1>
    <p class="mb-6 text-sm text-slate-500">Runde: <?= htmlspecialchars($runde['typ']) ?> · Status: <?= htmlspecialchars($runde['status']) ?></p>

    <div class="mb-6 rounded-2xl bg-white p-6 shadow">
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Nächste Startnummer(n) freigeben</h2>
        <?php if (empty($offene)): ?>
            <p class="text-sm text-slate-500">Alle Teilnehmer:innen dieser Runde wurden bereits freigegeben.</p>
        <?php else: ?>
            <form method="post" action="/admin/runden/<?= (int) $runde['id'] ?>/freigeben" class="space-y-2">
                <?php foreach ($offene as $t): ?>
                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                        <input type="checkbox" name="teilnehmer_ids[]" value="<?= (int) $t['id'] ?>" class="h-5 w-5">
                        <span>#<?= (int) $t['startnummer'] ?> – <?= htmlspecialchars($t['name']) ?><?= $t['name_p2'] ? ' &amp; ' . htmlspecialchars($t['name_p2']) : '' ?></span>
                    </label>
                <?php endforeach; ?>
                <button type="submit" class="mt-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Auswahl freigeben
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="mb-6 rounded-2xl bg-white p-6 shadow">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800">Live-Status</h2>
            <div class="flex items-center gap-2">
                <span id="aktualisiert-hinweis" class="text-xs text-slate-400"></span>
                <button type="button" onclick="ladeRundenStatus(true)"
                        class="rounded-lg bg-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-300">
                    ↻ Aktualisieren
                </button>
            </div>
        </div>
        <div id="live-status"></div>
    </div>

    <form method="post" action="/admin/runden/<?= (int) $runde['id'] ?>/abschliessen">
        <button type="submit" id="abschliessen-button" <?= $kannAbschliessen ? '' : 'disabled' ?>
                class="w-full rounded-lg px-4 py-3 font-semibold text-white <?= $kannAbschliessen ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-300' ?>">
            <?= $kannAbschliessen ? 'Runde abschliessen' : 'Runde abschliessen (noch nicht alle bewertet)' ?>
        </button>
    </form>

    <script>
        const rundeId = <?= (int) $runde['id'] ?>;
        const initialLiveStatus = <?= json_encode(array_values($live)) ?>;
        const initialKannAbschliessen = <?= $kannAbschliessen ? 'true' : 'false' ?>;
        let letzterStand = null;

        function fluchtHtml(text) {
            const div = document.createElement('div');
            div.textContent = text ?? '';
            return div.innerHTML;
        }

        function zeichneLiveStatus(live) {
            const container = document.getElementById('live-status');

            if (live.length === 0) {
                container.innerHTML = '<p class="text-sm text-slate-500">Noch keine Startnummer freigegeben.</p>';
                return;
            }

            container.innerHTML = `
                <table class="w-full text-left text-sm">
                    <thead>
                    <tr class="border-b text-slate-500">
                        <th class="py-2">Startnummer</th>
                        <th class="py-2">Name</th>
                        <th class="py-2">Richter fertig</th>
                    </tr>
                    </thead>
                    <tbody>
                        ${live.map((t) => {
                            const nameZusatz = t.name_p2 ? ' &amp; ' + fluchtHtml(t.name_p2) : '';
                            const fertig = parseInt(t.anzahl_richter_fertig, 10);
                            const farbe = fertig >= 5 ? 'text-emerald-600 font-semibold' : '';
                            return `
                                <tr class="border-b">
                                    <td class="py-2">#${t.startnummer}</td>
                                    <td class="py-2">${fluchtHtml(t.name)}${nameZusatz}</td>
                                    <td class="py-2 ${farbe}">${fertig} / 5</td>
                                </tr>`;
                        }).join('')}
                    </tbody>
                </table>`;
        }

        function setzeAbschliessenButton(kannAbschliessen) {
            const button = document.getElementById('abschliessen-button');
            button.disabled = !kannAbschliessen;
            button.textContent = kannAbschliessen ? 'Runde abschliessen' : 'Runde abschliessen (noch nicht alle bewertet)';
            button.classList.toggle('bg-emerald-600', kannAbschliessen);
            button.classList.toggle('hover:bg-emerald-700', kannAbschliessen);
            button.classList.toggle('bg-slate-300', !kannAbschliessen);
            button.classList.toggle('cursor-not-allowed', !kannAbschliessen);
        }

        async function ladeRundenStatus(erzwingen = false) {
            try {
                const response = await fetch(`/admin/runden/${rundeId}/status`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!response.ok) {
                    return;
                }

                const daten = await response.json();
                const neuerStand = JSON.stringify(daten);

                if (erzwingen || neuerStand !== letzterStand) {
                    letzterStand = neuerStand;
                    zeichneLiveStatus(daten.live);
                    setzeAbschliessenButton(daten.kannAbschliessen);
                }

                document.getElementById('aktualisiert-hinweis').textContent =
                    'zuletzt aktualisiert: ' + new Date().toLocaleTimeString('de-CH');
            } catch (e) {
                // Netzwerkfehler beim Hintergrund-Update stillschweigend ignorieren,
                // nächster Poll versucht es automatisch erneut.
            }
        }

        letzterStand = JSON.stringify({ live: initialLiveStatus, kannAbschliessen: initialKannAbschliessen });
        zeichneLiveStatus(initialLiveStatus);
        document.getElementById('aktualisiert-hinweis').textContent =
            'zuletzt aktualisiert: ' + new Date().toLocaleTimeString('de-CH');

        setInterval(() => ladeRundenStatus(false), 4000);
    </script>
</div>
