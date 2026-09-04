<div class="flex min-h-screen flex-col items-center justify-center gap-4 p-6 text-center">
    <div class="text-5xl">☕</div>
    <h1 class="text-2xl font-bold text-slate-800">Warte auf nächste Runde</h1>
    <p class="max-w-sm text-slate-500">
        Aktuell ist keine Startnummer zur Bewertung freigegeben. Diese Seite
        aktualisiert sich automatisch, sobald es weitergeht.
    </p>
    <form method="post" action="/kampfrichter/logout" class="mt-4">
        <button type="submit" class="text-sm text-slate-400 hover:underline">Abmelden</button>
    </form>
</div>

<script>
    async function pruefeStatus() {
        try {
            const response = await fetch('/kampfrichter/bewertung/status', { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                return;
            }
            const daten = await response.json();
            if (daten.status !== 'leerlauf') {
                window.location.href = '/kampfrichter/bewertung';
            }
        } catch (e) {
            // Netzwerkfehler beim Polling ignorieren, nächster Versuch folgt automatisch.
        }
    }

    setInterval(pruefeStatus, 2000);
</script>
