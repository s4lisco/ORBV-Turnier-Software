<x-layout title="Turniere – ORBV Turnier">
    <div class="mx-auto max-w-3xl p-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>

        <div class="mb-6 mt-2 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-800">Turniere</h1>
            <a href="{{ route('admin.turniere.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                + Neues Turnier
            </a>
        </div>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-emerald-100 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <div class="space-y-2">
            @forelse ($turniere as $turnier)
                <a href="{{ route('admin.turniere.show', $turnier) }}"
                   class="flex items-center justify-between rounded-xl bg-white p-4 shadow hover:bg-slate-50">
                    <div>
                        <div class="font-medium">{{ $turnier->name }}</div>
                        <div class="text-sm text-slate-500">{{ $turnier->datum->format('d.m.Y') }} · Gastgeber: {{ $turnier->verein->name }}</div>
                    </div>
                    <span class="text-blue-600">verwalten →</span>
                </a>
            @empty
                <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">Noch keine Turniere angelegt.</p>
            @endforelse
        </div>
    </div>
</x-layout>
