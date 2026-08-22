<x-layout title="Neues Turnier – ORBV Turnier">
    <div class="mx-auto max-w-md p-6">
        <a href="{{ route('admin.turniere.index') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück</a>
        <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Neues Turnier</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.turniere.store') }}" class="space-y-4 rounded-2xl bg-white p-6 shadow">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Beschreibung</label>
                <textarea name="beschreibung" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">{{ old('beschreibung') }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Adresse</label>
                <input type="text" name="adresse" value="{{ old('adresse') }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Datum</label>
                <input type="date" name="datum" value="{{ old('datum') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Organisierender Verein</label>
                <select name="verein_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    <option value="">– auswählen –</option>
                    @foreach ($vereine as $verein)
                        <option value="{{ $verein->id }}" @selected((int) old('verein_id') === $verein->id)>{{ $verein->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kategorien</label>
                <div class="space-y-1">
                    @foreach ($kategorien as $kategorie)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="kategorie_ids[]" value="{{ $kategorie->id }}"
                                   @checked(in_array($kategorie->id, old('kategorie_ids', [])))>
                            {{ $kategorie->name }} <span class="text-slate-400">({{ $kategorie->typ }})</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                Turnier anlegen
            </button>
        </form>
    </div>
</x-layout>
