<x-layout title="Organisator-Dashboard – ORBV Turnier">
    <div class="mx-auto max-w-4xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-800">Administration</h1>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="text-sm text-slate-500 hover:underline">Abmelden</button>
            </form>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <a href="{{ route('admin.vereine.index') }}" class="rounded-xl bg-white p-4 text-center shadow hover:bg-slate-50">
                <div class="text-2xl">🏛️</div>
                <div class="mt-1 text-sm font-medium">Vereine ({{ $anzahlVereine }})</div>
            </a>
            <a href="{{ route('admin.kategorien.index') }}" class="rounded-xl bg-white p-4 text-center shadow hover:bg-slate-50">
                <div class="text-2xl">🏷️</div>
                <div class="mt-1 text-sm font-medium">Kategorien ({{ $anzahlKategorien }})</div>
            </a>
            <a href="{{ route('admin.turniere.index') }}" class="rounded-xl bg-white p-4 text-center shadow hover:bg-slate-50">
                <div class="text-2xl">🏆</div>
                <div class="mt-1 text-sm font-medium">Turniere ({{ $turniere->count() }})</div>
            </a>
            <a href="{{ route('admin.einstellungen.edit') }}" class="rounded-xl bg-white p-4 text-center shadow hover:bg-slate-50">
                <div class="text-2xl">🖼️</div>
                <div class="mt-1 text-sm font-medium">Header (Logo &amp; Infotext)</div>
            </a>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow">
            <h2 class="mb-4 text-lg font-semibold text-slate-800">Turniere</h2>
            @if ($turniere->isEmpty())
                <p class="text-sm text-slate-500">Noch keine Turniere angelegt.</p>
            @else
                <div class="space-y-2">
                    @foreach ($turniere as $turnier)
                        <a href="{{ route('admin.turniere.show', $turnier) }}"
                           class="flex items-center justify-between rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                            <div>
                                <div class="font-medium">{{ $turnier->name }}</div>
                                <div class="text-sm text-slate-500">{{ $turnier->datum->format('d.m.Y') }} · Gastgeber: {{ $turnier->verein->name }}</div>
                            </div>
                            <span class="text-blue-600">verwalten →</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layout>
