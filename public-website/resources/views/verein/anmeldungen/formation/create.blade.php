<x-layout title="Formation anmelden – ORBV Turnier">
    <div class="mx-auto max-w-2xl p-6">
        <a href="{{ route('verein.turniere.show', $turnier) }}" class="text-sm text-blue-600 hover:underline">&larr; zurück</a>
        <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Formation anmelden – {{ $kategorie->name }}</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('verein.anmeldungen.formation.store', [$turnier, $kategorie]) }}" class="rounded-2xl bg-white p-6 shadow">
            @csrf

            <div id="formationen-liste" class="space-y-3">
                <div class="formation-row flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 p-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name der Formation</label>
                        <input type="text" name="formationen[0][name]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Anzahl Tänzerinnen</label>
                        <input type="number" name="formationen[0][anzahl_taenzerinnen]" min="1" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <button type="button" onclick="entfernenFormationZeile(this)" class="ml-auto text-sm text-red-600 hover:underline">entfernen</button>
                </div>
            </div>

            <template id="formation-template">
                <div class="formation-row flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 p-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name der Formation</label>
                        <input type="text" name="formationen[__INDEX__][name]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Anzahl Tänzerinnen</label>
                        <input type="number" name="formationen[__INDEX__][anzahl_taenzerinnen]" min="1" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <button type="button" onclick="entfernenFormationZeile(this)" class="ml-auto text-sm text-red-600 hover:underline">entfernen</button>
                </div>
            </template>

            <button type="button" onclick="hinzufuegenFormationZeile()"
                    class="mt-3 rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                + weitere Formation hinzufügen
            </button>

            <button type="submit"
                    class="mt-6 w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                Anmelden
            </button>
        </form>
    </div>

    <script>
        let formationIndex = 1;

        function hinzufuegenFormationZeile() {
            const tpl = document.getElementById('formation-template').content.cloneNode(true);
            tpl.querySelectorAll('[name]').forEach((el) => {
                el.name = el.name.replace('__INDEX__', formationIndex);
            });
            document.getElementById('formationen-liste').appendChild(tpl);
            formationIndex++;
        }

        function entfernenFormationZeile(button) {
            const zeilen = document.querySelectorAll('#formationen-liste .formation-row');
            if (zeilen.length > 1) {
                button.closest('.formation-row').remove();
            }
        }
    </script>
</x-layout>
