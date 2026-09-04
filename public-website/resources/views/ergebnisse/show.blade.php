<x-layout :title="$turnier->name . ' – Ergebnisse – ORBV Turnier'">
    <x-orbv-header />
    <div class="mx-auto max-w-3xl p-6">
        <a href="{{ route('ergebnisse.index') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Übersicht</a>

        <h1 class="mb-1 mt-2 text-2xl font-bold text-slate-800">{{ $turnier->name }}</h1>
        <p class="mb-6 text-sm text-slate-500">{{ $turnier->datum->format('d.m.Y') }} · {{ $turnier->adresse }}</p>

        @forelse ($turnier->turnierKategorien as $turnierKategorie)
            @php
                $eintraege = $turnierKategorie->ranglisteEintraege
                    ->sortBy(fn ($e) => $e->istDnf() ? PHP_INT_MAX : (int) $e->rang)
                    ->values();
            @endphp
            <div class="mb-6 rounded-2xl bg-white p-6 shadow">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">{{ $turnierKategorie->kategorie->name }}</h2>
                <table class="w-full text-left text-sm">
                    <thead>
                    <tr class="border-b text-slate-500">
                        <th class="py-2 pr-4">Rang</th>
                        <th class="py-2">Name</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($eintraege as $eintrag)
                        <tr class="border-b {{ $eintrag->istDnf() ? 'text-slate-400' : '' }}">
                            <td class="py-2 pr-4 font-semibold">{{ $eintrag->rang }}</td>
                            <td class="py-2">{{ $eintrag->name }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">
                Für dieses Turnier sind noch keine Ergebnisse veröffentlicht.
            </p>
        @endforelse
    </div>
</x-layout>
