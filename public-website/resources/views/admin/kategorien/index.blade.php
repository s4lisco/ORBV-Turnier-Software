<x-layout title="Kategorien – ORBV Turnier">
    <div class="mx-auto max-w-3xl p-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>

        <div class="mb-6 mt-2">
            <h1 class="text-2xl font-bold text-slate-800">Kategorien</h1>
            <p class="mt-1 text-sm text-slate-500">Die 10 Kategorien sind fest hinterlegt. Name, Typ und Bewertungskriterien können bearbeitet werden.</p>
        </div>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-emerald-100 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <div class="space-y-2">
            @forelse ($kategorien as $kategorie)
                <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow">
                    <div>
                        <div class="font-medium">{{ $kategorie->name }} <span class="text-slate-400">({{ $kategorie->typ }})</span></div>
                        <div class="text-sm text-slate-500">Kriterien: {{ $kategorie->bewertungen->pluck('name')->join(', ') ?: '–' }}</div>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <a href="{{ route('admin.kategorien.edit', $kategorie) }}" class="text-blue-600 hover:underline">bearbeiten</a>
                    </div>
                </div>
            @empty
                <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">Noch keine Kategorien angelegt.</p>
            @endforelse
        </div>
    </div>
</x-layout>
