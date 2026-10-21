<div class="mx-auto max-w-3xl p-6">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($kategorieName) ?></h1>
        <form method="post" action="/kampfrichter/logout">
            <button type="submit" class="text-sm text-slate-400 hover:underline">Abmelden</button>
        </form>
    </div>

    <?php if ($error): ?>
        <p class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/kampfrichter/bewertung/<?= (int) $rundeId ?>" class="space-y-6">
        <div class="grid grid-cols-1 gap-6<?= $paarweise ? ' sm:grid-cols-2' : '' ?>">
            <?php foreach ($teilnehmer as $t): ?>
                <div class="rounded-2xl bg-white p-6 shadow" data-teilnehmer-block>
                    <h2 class="mb-3 text-lg font-semibold text-slate-800">
                        #<?= (int) $t['startnummer'] ?> – <?= htmlspecialchars($t['name']) ?><?= $t['name_p2'] ? ' &amp; ' . htmlspecialchars($t['name_p2']) : '' ?>
                    </h2>

                    <label class="mb-4 flex items-center gap-2 text-sm font-medium text-red-600">
                        <input type="checkbox" class="dnf-checkbox h-4 w-4" onchange="toggleDnf(this)">
                        Nicht getanzt
                    </label>
                    <input type="hidden" name="nicht_getanzt[<?= (int) $t['id'] ?>]" value="0" class="dnf-hidden">

                    <div class="space-y-4 kriterien-felder">
                        <?php foreach ($kriterien as $kriterium): ?>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700"><?= htmlspecialchars($kriterium['name']) ?> (0–10)</label>
                                <input type="number" name="punkte[<?= (int) $t['id'] ?>][<?= (int) $kriterium['id'] ?>]" min="0" max="10" step="0.5" required
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-lg focus:border-emerald-500 focus:outline-none">
                            </div>
                        <?php endforeach; ?>

                        <div class="rounded-lg border border-slate-200 p-3 fehler-feld">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Fehler (Minuspunkte)</label>
                            <div class="mb-2 flex gap-2">
                                <select class="fehler-auswahl rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                    <option value="0">0</option>
                                    <option value="-1">-1</option>
                                    <option value="-5">-5</option>
                                    <option value="-10">-10</option>
                                </select>
                                <button type="button" class="rounded-lg bg-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-300" onclick="fehlerHinzufuegen(this, <?= (int) $t['id'] ?>)">
                                    + hinzufügen
                                </button>
                            </div>
                            <div class="fehler-liste space-y-1 text-sm"></div>
                            <p class="mt-1 text-sm font-semibold text-red-600">Summe: <span class="fehler-summe">0</span></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-lg font-semibold text-white hover:bg-emerald-700">
            Bewertung absenden (endgültig)
        </button>
    </form>
</div>

<script>
    function toggleDnf(checkbox) {
        const block = checkbox.closest('[data-teilnehmer-block]');
        const hidden = block.querySelector('.dnf-hidden');
        const felder = block.querySelectorAll('.kriterien-felder input[type="number"]');

        hidden.value = checkbox.checked ? '1' : '0';
        felder.forEach((feld) => {
            feld.required = !checkbox.checked;
            feld.disabled = checkbox.checked;
            feld.closest('div').classList.toggle('opacity-40', checkbox.checked);
        });

        const fehlerFeld = block.querySelector('.fehler-feld');
        fehlerFeld.classList.toggle('opacity-40', checkbox.checked);
        fehlerFeld.querySelectorAll('select, button').forEach((el) => {
            el.disabled = checkbox.checked;
        });
    }

    function fehlerHinzufuegen(button, teilnehmerId) {
        const feld = button.closest('.fehler-feld');
        const auswahl = feld.querySelector('.fehler-auswahl');
        const wert = auswahl.value;

        const zeile = document.createElement('div');
        zeile.className = 'flex items-center justify-between rounded bg-slate-100 px-2 py-1 fehler-eintrag';
        zeile.dataset.wert = wert;
        zeile.innerHTML = `
            <span>${wert}</span>
            <input type="hidden" name="fehler[${teilnehmerId}][]" value="${wert}">
            <button type="button" class="text-xs text-slate-500 hover:underline">entfernen</button>
        `;
        zeile.querySelector('button').addEventListener('click', () => {
            zeile.remove();
            fehlerSummeAktualisieren(feld);
        });

        feld.querySelector('.fehler-liste').appendChild(zeile);
        fehlerSummeAktualisieren(feld);
    }

    function fehlerSummeAktualisieren(feld) {
        const eintraege = feld.querySelectorAll('.fehler-eintrag');
        let summe = 0;
        eintraege.forEach((eintrag) => {
            summe += parseFloat(eintrag.dataset.wert);
        });
        feld.querySelector('.fehler-summe').textContent = summe;
    }
</script>
