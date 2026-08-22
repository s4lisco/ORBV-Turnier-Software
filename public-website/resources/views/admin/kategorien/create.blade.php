<x-layout title="Neue Kategorie – ORBV Turnier">
    <div class="mx-auto max-w-md p-6">
        <a href="{{ route('admin.kategorien.index') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück</a>
        <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Neue Kategorie</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.kategorien.store') }}" class="space-y-4 rounded-2xl bg-white p-6 shadow">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Typ</label>
                <select name="typ" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    <option value="solo" @selected(old('typ') === 'solo')>Solo</option>
                    <option value="paar" @selected(old('typ') === 'paar')>Paar</option>
                    <option value="formation" @selected(old('typ') === 'formation')>Formation</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Bewertungskriterien (kommagetrennt)</label>
                <input type="text" name="kriterien" value="{{ old('kriterien', 'Tanz, Ausdruck, Technik') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                Kategorie anlegen
            </button>
        </form>
    </div>
</x-layout>
