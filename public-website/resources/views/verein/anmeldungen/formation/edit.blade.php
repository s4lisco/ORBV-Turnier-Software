<x-layout title="Formation-Anmeldung bearbeiten – ORBV Turnier">
    <div class="mx-auto max-w-md p-6">
        <a href="{{ route('verein.turniere.show', $turnier) }}" class="text-sm text-blue-600 hover:underline">&larr; zurück</a>
        <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Anmeldung bearbeiten – {{ $kategorie->name }}</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('verein.anmeldungen.formation.update', [$turnier, $kategorie, $anmeldungFormation]) }}" class="space-y-4 rounded-2xl bg-white p-6 shadow">
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Name der Formation</label>
                <input type="text" name="name" value="{{ old('name', $anmeldungFormation->name) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Anzahl Tänzerinnen</label>
                <input type="number" name="anzahl_taenzerinnen" value="{{ old('anzahl_taenzerinnen', $anmeldungFormation->anzahl_taenzerinnen) }}" min="1" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                Speichern
            </button>
        </form>
    </div>
</x-layout>
