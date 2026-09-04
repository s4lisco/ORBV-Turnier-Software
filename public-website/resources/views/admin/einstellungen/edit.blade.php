<x-layout title="Header-Einstellungen – ORBV Turnier">
    <div class="mx-auto max-w-2xl p-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>
        <h1 class="mb-2 mt-2 text-2xl font-bold text-slate-800">Header-Einstellungen</h1>
        <p class="mb-6 text-sm text-slate-500">
            Logo und Infotext werden im öffentlichen Header auf turnier.orbv.ch angezeigt.
        </p>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-emerald-100 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.einstellungen.update') }}" enctype="multipart/form-data" class="space-y-4 rounded-2xl bg-white p-6 shadow">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Logo</label>
                @if ($einstellung->logoUrl())
                    <img src="{{ $einstellung->logoUrl() }}" alt="Aktuelles ORBV-Logo" class="mb-2 h-16">
                @else
                    <p class="mb-2 text-sm text-slate-400">Noch kein Logo hochgeladen – es wird ein Platzhalter angezeigt.</p>
                @endif
                <input type="file" name="logo" accept="image/*"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Infotext zum ORBV</label>
                <textarea name="info_text" rows="3"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none"
                          placeholder="Ostschweizer Rock'n'Roll und Boogie Woogie Verband">{{ old('info_text', $einstellung->info_text) }}</textarea>
            </div>

            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                Speichern
            </button>
        </form>
    </div>
</x-layout>
