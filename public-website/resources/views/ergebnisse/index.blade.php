<x-layout title="Ergebnisse – ORBV Turnier">
    <x-orbv-header />
    <div class="mx-auto max-w-3xl p-6">
        <a href="{{ route('home') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück</a>
        <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Ergebnisse</h1>

        <div class="space-y-2">
            @forelse ($turniere as $turnier)
                <a href="{{ route('ergebnisse.show', $turnier) }}"
                   class="block rounded-xl bg-white p-4 shadow hover:bg-slate-50">
                    <div class="font-medium">{{ $turnier->name }}</div>
                    <div class="text-sm text-slate-500">{{ $turnier->datum->format('d.m.Y') }}</div>
                </a>
            @empty
                <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">
                    Noch keine Ergebnisse veröffentlicht.
                </p>
            @endforelse
        </div>
    </div>
</x-layout>
