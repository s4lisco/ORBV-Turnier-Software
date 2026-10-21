<x-layout :title="$turnier->name . ' – ORBV Turnier'">
    <div class="mx-auto max-w-3xl p-6">
        <a href="{{ route('verein.dashboard') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Übersicht</a>

        <div class="mb-6 mt-2">
            <h1 class="text-2xl font-bold text-slate-800">{{ $turnier->name }}</h1>
            <p class="text-sm text-slate-500">{{ $turnier->datum->format('d.m.Y') }} · {{ $turnier->adresse }}</p>
            <p class="mt-1 text-sm">
                @if ($turnier->istAnmeldungOffenFuerVerein())
                    <span class="text-emerald-600">Anmeldung offen bis {{ $turnier->anmeldeschluss->format('d.m.Y') }}</span>
                @else
                    <span class="text-red-600">Anmeldefrist abgelaufen seit {{ $turnier->anmeldeschluss->format('d.m.Y') }} – Änderungen nur noch durch den Verband</span>
                @endif
            </p>
        </div>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-emerald-100 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        @forelse ($turnier->turnierKategorien as $turnierKategorie)
            @php
                $kategorie = $turnierKategorie->kategorie;
                $ranglisteByName = $turnierKategorie->ranglisteEintraege->keyBy('name');
            @endphp
            <div class="mb-6 rounded-2xl bg-white p-6 shadow">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">{{ $kategorie->name }} <span class="text-slate-400">({{ $kategorie->typ }})</span></h2>
                    @if ($turnier->istAnmeldungOffenFuerVerein())
                        <a href="{{ route('verein.anmeldungen.' . $kategorie->typ . '.create', [$turnier, $kategorie]) }}"
                           class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700">
                            + Anmelden
                        </a>
                    @endif
                </div>

                @if ($kategorie->typ === 'solo')
                    @forelse ($turnierKategorie->anmeldungenSolo as $a)
                        @php $rang = $ranglisteByName->get($a->name)?->rang; @endphp
                        <div class="flex items-center justify-between border-t border-slate-100 py-2 text-sm">
                            <span>
                                {{ $a->name }} ({{ $a->geburtsdatum->format('d.m.Y') }})
                                @if ($rang)
                                    <span class="ml-2 rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Rang {{ $rang }}</span>
                                @endif
                            </span>
                            @if ($turnier->istAnmeldungOffenFuerVerein())
                                <span class="flex gap-3">
                                    <a href="{{ route('verein.anmeldungen.solo.edit', [$turnier, $kategorie, $a]) }}" class="text-blue-600 hover:underline">bearbeiten</a>
                                    <form method="POST" action="{{ route('verein.anmeldungen.solo.destroy', [$turnier, $kategorie, $a]) }}" onsubmit="return confirm('Anmeldung wirklich zurückziehen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">abmelden</button>
                                    </form>
                                </span>
                            @endif
                        </div>
                    @empty
                        <p class="py-2 text-sm text-slate-400">Noch keine eigene Anmeldung.</p>
                    @endforelse
                @endif

                @if ($kategorie->typ === 'paar')
                    @forelse ($turnierKategorie->anmeldungenPaar as $a)
                        @php $rang = $ranglisteByName->get($a->name_p1 . ' & ' . $a->name_p2)?->rang; @endphp
                        <div class="flex items-center justify-between border-t border-slate-100 py-2 text-sm">
                            <span>
                                {{ $a->name_p1 }} &amp; {{ $a->name_p2 }}
                                @if ($rang)
                                    <span class="ml-2 rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Rang {{ $rang }}</span>
                                @endif
                            </span>
                            @if ($turnier->istAnmeldungOffenFuerVerein())
                                <span class="flex gap-3">
                                    <a href="{{ route('verein.anmeldungen.paar.edit', [$turnier, $kategorie, $a]) }}" class="text-blue-600 hover:underline">bearbeiten</a>
                                    <form method="POST" action="{{ route('verein.anmeldungen.paar.destroy', [$turnier, $kategorie, $a]) }}" onsubmit="return confirm('Anmeldung wirklich zurückziehen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">abmelden</button>
                                    </form>
                                </span>
                            @endif
                        </div>
                    @empty
                        <p class="py-2 text-sm text-slate-400">Noch keine eigene Anmeldung.</p>
                    @endforelse
                @endif

                @if ($kategorie->typ === 'formation')
                    @forelse ($turnierKategorie->anmeldungenFormation as $a)
                        @php $rang = $ranglisteByName->get($a->name)?->rang; @endphp
                        <div class="flex items-center justify-between border-t border-slate-100 py-2 text-sm">
                            <span>
                                {{ $a->name }} ({{ $a->anzahl_taenzerinnen }} Tänzerinnen)
                                @if ($rang)
                                    <span class="ml-2 rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Rang {{ $rang }}</span>
                                @endif
                            </span>
                            @if ($turnier->istAnmeldungOffenFuerVerein())
                                <span class="flex gap-3">
                                    <a href="{{ route('verein.anmeldungen.formation.edit', [$turnier, $kategorie, $a]) }}" class="text-blue-600 hover:underline">bearbeiten</a>
                                    <form method="POST" action="{{ route('verein.anmeldungen.formation.destroy', [$turnier, $kategorie, $a]) }}" onsubmit="return confirm('Anmeldung wirklich zurückziehen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">abmelden</button>
                                    </form>
                                </span>
                            @endif
                        </div>
                    @empty
                        <p class="py-2 text-sm text-slate-400">Noch keine eigene Anmeldung.</p>
                    @endforelse
                @endif
            </div>
        @empty
            <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">Für dieses Turnier sind noch keine Kategorien ausgeschrieben.</p>
        @endforelse
    </div>
</x-layout>
