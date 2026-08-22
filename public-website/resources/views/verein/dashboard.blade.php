<x-layout title="Meine Turniere – ORBV Turnier">
    <div class="mx-auto max-w-3xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-800">Turniere</h1>
            <form method="POST" action="{{ route('verein.logout') }}">
                @csrf
                <button type="submit" class="text-sm text-slate-500 hover:underline">Abmelden</button>
            </form>
        </div>

        <div class="space-y-2">
            @forelse ($turniere as $turnier)
                <a href="{{ route('verein.turniere.show', $turnier) }}"
                   class="flex items-center justify-between rounded-xl bg-white p-4 shadow hover:bg-slate-50">
                    <div>
                        <div class="font-medium">{{ $turnier->name }}</div>
                        <div class="text-sm text-slate-500">{{ $turnier->datum->format('d.m.Y') }} · {{ $turnier->adresse }}</div>
                    </div>
                    @if ($turnier->istAnmeldungOffenFuerVerein())
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">Anmeldung offen</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">Anmeldung geschlossen</span>
                    @endif
                </a>
            @empty
                <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">Noch keine Turniere vorhanden.</p>
            @endforelse
        </div>
    </div>
</x-layout>
