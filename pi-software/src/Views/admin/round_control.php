<div class="mx-auto max-w-2xl p-6">
    <a href="/admin/runden" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Übersicht</a>
    <h1 class="mb-1 mt-2 text-2xl font-bold text-slate-800"><?= htmlspecialchars($runde['kategorie_name']) ?></h1>
    <p class="mb-1 text-sm text-slate-500">Runde: <?= htmlspecialchars($runde['typ']) ?> · Status: <?= htmlspecialchars($runde['status']) ?></p>
    <p class="mb-6 text-sm">
        <a href="/admin/runden/<?= (int) $runde['id'] ?>/startliste" class="text-blue-600 hover:underline">Startliste ansehen &amp; drucken →</a>
    </p>

    <div class="mb-6 rounded-2xl bg-white p-6 shadow">
        <h2 class="mb-1 text-lg font-semibold text-slate-800">Ablauf</h2>
        <p class="text-sm text-slate-500">
            Die nächste(n) Startnummer(n) werden automatisch für die Wertungsrichter freigegeben,
            sobald alle 5 die aktuelle(n) Startnummer(n) bewertet haben – kein manueller Schritt nötig.
        </p>
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

    <form method="post" action="/admin/runden/<?= (int) $runde['id'] ?>/abschliessen" class="mb-3">
        <button type="submit" id="abschliessen-button" <?= $kannAbschliessen ? '' : 'disabled' ?>
                class="w-full rounded-lg px-4 py-3 font-semibold text-white <?= $kannAbschliessen ? 'bg-emerald-600 hover:bg-emerald-700' : 'cursor-not-allowed bg-slate-300' ?>">
            <?= $kannAbschliessen ? 'Runde abschliessen' : 'Runde abschliessen (noch nicht alle bewertet)' ?>
        </button>
    </form>

    <form method="post" action="/admin/runden/<?= (int) $runde['id'] ?>/abbrechen"
          id="abbrechen-form" class="<?= $kannAbbrechen ? '' : 'hidden' ?>"
          onsubmit="return confirm('Runde wirklich abbrechen? Alle Startnummern-Freigaben werden zurückgesetzt.')">
        <button type="submit" class="w-full rounded-lg border border-red-300 bg-white px-4 py-3 font-semibold text-red-600 hover:bg-red-50">
            Runde abbrechen
        </button>
    </form>
    <p id="abbrechen-hinweis" class="text-center text-xs text-slate-400 <?= $kannAbbrechen ? 'hidden' : '' ?>">
        Ein Abbruch ist nur möglich, solange noch keine Bewertung abgegeben wurde.
    </p>

    <script>
        const rundeId = <?= (int) $runde['id'] ?>;
        const initialLiveStatus = <?= json_encode(array_values($live)) ?>;
        const initialKannAbschliessen = <?= $kannAbschliessen ? 'true' : 'false' ?>;
        const initialKannAbbrechen = <?= $kannAbbrechen ? 'true' : 'false' ?>;
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
                                    <td class="py-2">#${t.startnummer ?? '–'}</td>
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

        function setzeAbbrechenSichtbarkeit(kannAbbrechen) {
            document.getElementById('abbrechen-form').classList.toggle('hidden', !kannAbbrechen);
            document.getElementById('abbrechen-hinweis').classList.toggle('hidden', kannAbbrechen);
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
                    setzeAbbrechenSichtbarkeit(daten.kannAbbrechen);
                }

                document.getElementById('aktualisiert-hinweis').textContent =
                    'zuletzt aktualisiert: ' + new Date().toLocaleTimeString('de-CH');
            } catch (e) {
                // Netzwerkfehler beim Hintergrund-Update stillschweigend ignorieren,
                // nächster Poll versucht es automatisch erneut.
            }
        }

        letzterStand = JSON.stringify({ live: initialLiveStatus, kannAbschliessen: initialKannAbschliessen, kannAbbrechen: initialKannAbbrechen });
        zeichneLiveStatus(initialLiveStatus);
        document.getElementById('aktualisiert-hinweis').textContent =
            'zuletzt aktualisiert: ' + new Date().toLocaleTimeString('de-CH');

        setInterval(() => ladeRundenStatus(false), 4000);
    </script>
</div>
