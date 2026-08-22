<div class="mx-auto max-w-xl p-6">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800">Offene Bewertungen</h1>
        <form method="post" action="/kampfrichter/logout">
            <button type="submit" class="text-sm text-slate-500 hover:underline">Abmelden</button>
        </form>
    </div>

    <div class="mb-3 flex items-center justify-between">
        <span id="aktualisiert-hinweis" class="text-xs text-slate-400"></span>
        <button type="button" onclick="ladeOffeneBewertungen(true)"
                class="rounded-lg bg-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-300">
            ↻ Aktualisieren
        </button>
    </div>

    <div id="offene-liste"></div>

    <script>
        const initialOffene = <?= json_encode(array_values($offene)) ?>;
        let letzterStand = null;

        function fluchtHtml(text) {
            const div = document.createElement('div');
            div.textContent = text ?? '';
            return div.innerHTML;
        }

        function zeichneOffeneListe(offene) {
            const container = document.getElementById('offene-liste');

            if (offene.length === 0) {
                container.innerHTML = `
                    <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">
                        Aktuell sind keine Startnummern zur Bewertung freigegeben.
                    </p>`;
                return;
            }

            container.innerHTML = `
                <div class="space-y-3">
                    ${offene.map((item) => {
                        const bewertet = item.bereits_bewertet > 0;
                        const nameZusatz = item.name_p2 ? ' &amp; ' + fluchtHtml(item.name_p2) : '';
                        return `
                            <a href="/kampfrichter/bewertung/${item.runde_id}/${item.teilnehmer_id}"
                               class="flex items-center justify-between rounded-xl bg-white p-4 shadow ${bewertet ? 'opacity-50' : 'hover:bg-slate-50'}">
                                <div>
                                    <div class="font-semibold">#${item.startnummer} – ${fluchtHtml(item.name)}${nameZusatz}</div>
                                    <div class="text-sm text-slate-500">${fluchtHtml(item.kategorie_name)} · ${fluchtHtml(item.runde_typ)}</div>
                                </div>
                                <span class="text-sm font-medium ${bewertet ? 'text-emerald-600' : 'text-blue-600'}">
                                    ${bewertet ? 'abgesendet' : 'bewerten →'}
                                </span>
                            </a>`;
                    }).join('')}
                </div>`;
        }

        async function ladeOffeneBewertungen(erzwingen = false) {
            try {
                const response = await fetch('/kampfrichter/bewertung/status', {
                    headers: { 'Accept': 'application/json' },
                });
                if (!response.ok) {
                    return;
                }

                const offene = await response.json();
                const neuerStand = JSON.stringify(offene);

                if (erzwingen || neuerStand !== letzterStand) {
                    letzterStand = neuerStand;
                    zeichneOffeneListe(offene);
                }

                document.getElementById('aktualisiert-hinweis').textContent =
                    'zuletzt aktualisiert: ' + new Date().toLocaleTimeString('de-CH');
            } catch (e) {
                // Netzwerkfehler beim Hintergrund-Update stillschweigend ignorieren,
                // nächster Poll versucht es automatisch erneut.
            }
        }

        letzterStand = JSON.stringify(initialOffene);
        zeichneOffeneListe(initialOffene);
        document.getElementById('aktualisiert-hinweis').textContent =
            'zuletzt aktualisiert: ' + new Date().toLocaleTimeString('de-CH');

        setInterval(() => ladeOffeneBewertungen(false), 4000);
    </script>
</div>
