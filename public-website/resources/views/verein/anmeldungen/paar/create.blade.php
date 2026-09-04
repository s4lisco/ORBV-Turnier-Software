<x-layout title="Paar anmelden – ORBV Turnier">
    <div class="mx-auto max-w-3xl p-6">
        <a href="{{ route('verein.turniere.show', $turnier) }}" class="text-sm text-blue-600 hover:underline">&larr; zurück</a>
        <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Paar anmelden – {{ $kategorie->name }}</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('verein.anmeldungen.paar.store', [$turnier, $kategorie]) }}" class="rounded-2xl bg-white p-6 shadow">
            @csrf

            <div id="paare-liste" class="space-y-3">
                <div class="paar-row flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 p-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name Tänzer:in 1</label>
                        <input type="text" name="paare[0][name_p1]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Geburtsdatum Tänzer:in 1</label>
                        <input type="date" name="paare[0][geburtsdatum_p1]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name Tänzer:in 2</label>
                        <input type="text" name="paare[0][name_p2]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Geburtsdatum Tänzer:in 2</label>
                        <input type="date" name="paare[0][geburtsdatum_p2]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <button type="button" onclick="entfernenPaarZeile(this)" class="ml-auto text-sm text-red-600 hover:underline">entfernen</button>
                </div>
            </div>

            <template id="paar-template">
                <div class="paar-row flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 p-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name Tänzer:in 1</label>
                        <input type="text" name="paare[__INDEX__][name_p1]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Geburtsdatum Tänzer:in 1</label>
                        <input type="date" name="paare[__INDEX__][geburtsdatum_p1]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name Tänzer:in 2</label>
                        <input type="text" name="paare[__INDEX__][name_p2]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Geburtsdatum Tänzer:in 2</label>
                        <input type="date" name="paare[__INDEX__][geburtsdatum_p2]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <button type="button" onclick="entfernenPaarZeile(this)" class="ml-auto text-sm text-red-600 hover:underline">entfernen</button>
                </div>
            </template>

            <button type="button" onclick="hinzufuegenPaarZeile()"
                    class="mt-3 rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                + weiteres Paar hinzufügen
            </button>

            <button type="submit"
                    class="mt-6 w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                Anmelden
            </button>
        </form>
    </div>

    <script>
        let paarIndex = 1;

        function hinzufuegenPaarZeile() {
            const tpl = document.getElementById('paar-template').content.cloneNode(true);
            tpl.querySelectorAll('[name]').forEach((el) => {
                el.name = el.name.replace('__INDEX__', paarIndex);
            });
            document.getElementById('paare-liste').appendChild(tpl);
            paarIndex++;
        }

        function entfernenPaarZeile(button) {
            const zeilen = document.querySelectorAll('#paare-liste .paar-row');
            if (zeilen.length > 1) {
                button.closest('.paar-row').remove();
            }
        }
    </script>
</x-layout>
