<x-layout title="Solo anmelden – ORBV Turnier">
    <div class="mx-auto max-w-2xl p-6">
        <a href="{{ route('verein.turniere.show', $turnier) }}" class="text-sm text-blue-600 hover:underline">&larr; zurück</a>
        <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Solo anmelden – {{ $kategorie->name }}</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('verein.anmeldungen.solo.store', [$turnier, $kategorie]) }}" class="rounded-2xl bg-white p-6 shadow">
            @csrf

            <div id="personen-liste" class="space-y-3">
                <div class="person-row flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 p-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                        <input type="text" name="personen[0][name]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Geburtsdatum</label>
                        <input type="date" name="personen[0][geburtsdatum]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <button type="button" onclick="entfernenPersonenZeile(this)" class="ml-auto text-sm text-red-600 hover:underline">entfernen</button>
                </div>
            </div>

            <template id="person-template">
                <div class="person-row flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 p-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                        <input type="text" name="personen[__INDEX__][name]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Geburtsdatum</label>
                        <input type="date" name="personen[__INDEX__][geburtsdatum]" required
                               class="rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    <button type="button" onclick="entfernenPersonenZeile(this)" class="ml-auto text-sm text-red-600 hover:underline">entfernen</button>
                </div>
            </template>

            <button type="button" onclick="hinzufuegenPersonenZeile()"
                    class="mt-3 rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                + weitere Person hinzufügen
            </button>

            <button type="submit"
                    class="mt-6 w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                Anmelden
            </button>
        </form>
    </div>

    <script>
        let personenIndex = 1;

        function hinzufuegenPersonenZeile() {
            const tpl = document.getElementById('person-template').content.cloneNode(true);
            tpl.querySelectorAll('[name]').forEach((el) => {
                el.name = el.name.replace('__INDEX__', personenIndex);
            });
            document.getElementById('personen-liste').appendChild(tpl);
            personenIndex++;
        }

        function entfernenPersonenZeile(button) {
            const zeilen = document.querySelectorAll('#personen-liste .person-row');
            if (zeilen.length > 1) {
                button.closest('.person-row').remove();
            }
        }
    </script>
</x-layout>
