<x-layout title="Paar-Anmeldung bearbeiten – ORBV Turnier">
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

        <form method="POST" action="{{ route('verein.anmeldungen.paar.update', [$turnier, $kategorie, $anmeldungPaar]) }}" class="space-y-4 rounded-2xl bg-white p-6 shadow">
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Name Tänzer:in 1</label>
                <input type="text" name="name_p1" value="{{ old('name_p1', $anmeldungPaar->name_p1) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Geburtsdatum Tänzer:in 1</label>
                <input type="date" name="geburtsdatum_p1" value="{{ old('geburtsdatum_p1', $anmeldungPaar->geburtsdatum_p1->format('Y-m-d')) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Name Tänzer:in 2</label>
                <input type="text" name="name_p2" value="{{ old('name_p2', $anmeldungPaar->name_p2) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Geburtsdatum Tänzer:in 2</label>
                <input type="date" name="geburtsdatum_p2" value="{{ old('geburtsdatum_p2', $anmeldungPaar->geburtsdatum_p2->format('Y-m-d')) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                Speichern
            </button>
        </form>
    </div>
</x-layout>
