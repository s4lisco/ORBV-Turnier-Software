<x-layout :title="$turnier->name . ' – ORBV Turnier'">
    <div class="mx-auto max-w-4xl p-6">
        <a href="{{ route('admin.turniere.index') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück zur Übersicht</a>

        <div class="mb-6 mt-2 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">{{ $turnier->name }}</h1>
                <p class="text-sm text-slate-500">
                    {{ $turnier->datum->format('d.m.Y') }} · {{ $turnier->adresse }} · Gastgeber: {{ $turnier->verein->name }}
                </p>
                <p class="mt-1 text-sm">
                    Anmeldefrist für Vereine:
                    @if ($turnier->istAnmeldungOffenFuerVerein())
                        <span class="text-emerald-600">offen bis {{ $turnier->anmeldeschluss->format('d.m.Y') }}</span>
                    @else
                        <span class="text-red-600">geschlossen seit {{ $turnier->anmeldeschluss->format('d.m.Y') }} (nur noch Admin)</span>
                    @endif
                </p>
            </div>
            <div class="flex flex-col items-end gap-2">
                <a href="{{ route('admin.turniere.export', $turnier) }}"
                   class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Teilnehmerliste exportieren
                </a>
                <a href="{{ route('admin.turniere.edit', $turnier) }}" class="text-sm text-blue-600 hover:underline">Turnier bearbeiten</a>
            </div>
        </div>

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

        <div class="mb-6 rounded-2xl bg-white p-6 shadow">
            <h2 class="mb-2 text-lg font-semibold text-slate-800">Rangliste hochladen</h2>
            <p class="mb-4 text-sm text-slate-500">
                CSV vom Pi (Menüpunkt "Rangliste für turnier.orbv.ch") hochladen, um die
                Endergebnisse auf der öffentlichen Ergebnisseite zu veröffentlichen.
                Ein erneuter Upload ersetzt zuvor veröffentlichte Ergebnisse der jeweiligen Kategorie.
            </p>
            <form method="POST" action="{{ route('admin.turniere.rangliste.upload', $turnier) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                @csrf
                <input type="file" name="rangliste_csv" accept=".csv,text/csv" required
                       class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <button type="submit" class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Hochladen &amp; veröffentlichen
                </button>
                @if ($turnier->turnierKategorien->contains(fn ($tk) => $tk->ranglisteEintraege->isNotEmpty()))
                    <a href="{{ route('ergebnisse.show', $turnier) }}" target="_blank" class="text-sm text-blue-600 hover:underline">
                        öffentliche Ergebnisseite ansehen
                    </a>
                @endif
            </form>
        </div>

        @forelse ($turnier->turnierKategorien as $turnierKategorie)
            @php
                $kategorie = $turnierKategorie->kategorie;
                $anzahlPersonen = match ($kategorie->typ) {
                    'solo' => $turnierKategorie->anmeldungenSolo->count(),
                    'paar' => $turnierKategorie->anmeldungenPaar->count() * 2,
                    'formation' => (int) $turnierKategorie->anmeldungenFormation->sum('anzahl_taenzerinnen'),
                    default => 0,
                };
            @endphp
            <div class="mb-6 rounded-2xl bg-white p-6 shadow">
                <h2 class="mb-4 text-lg font-semibold text-slate-800">
                    {{ $kategorie->name }} <span class="text-slate-400">({{ $kategorie->typ }})</span>
                    <span class="text-sm font-normal text-slate-500">– {{ $anzahlPersonen }} Personen</span>
                </h2>

                @if ($kategorie->typ === 'solo')
                    <div class="mb-4 space-y-2">
                        @forelse ($turnierKategorie->anmeldungenSolo as $a)
                            <div class="flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 p-3">
                                <form method="POST" action="{{ route('admin.turniere.anmeldungen.solo.update', [$turnier, $a]) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="block text-xs text-slate-500">Name</label>
                                        <input type="text" name="name" value="{{ $a->name }}" class="rounded border border-slate-300 px-2 py-1">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500">Geburtsdatum</label>
                                        <input type="date" name="geburtsdatum" value="{{ $a->geburtsdatum->format('Y-m-d') }}" class="rounded border border-slate-300 px-2 py-1">
                                    </div>
                                    <span class="pb-1.5 text-sm text-slate-500">{{ $a->verein->name }}</span>
                                    <button type="submit" class="text-sm text-blue-600 hover:underline">speichern</button>
                                </form>
                                <form method="POST" action="{{ route('admin.turniere.anmeldungen.solo.destroy', [$turnier, $a]) }}" onsubmit="return confirm('Wirklich löschen?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">löschen</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">Keine Anmeldungen.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('admin.turniere.anmeldungen.solo.store', [$turnier, $kategorie]) }}" class="flex flex-wrap items-end gap-2 rounded-lg bg-slate-50 p-3">
                        @csrf
                        <div>
                            <label class="block text-xs text-slate-500">Name</label>
                            <input type="text" name="name" required class="rounded border border-slate-300 px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Geburtsdatum</label>
                            <input type="date" name="geburtsdatum" required class="rounded border border-slate-300 px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Verein</label>
                            <select name="verein_id" required class="rounded border border-slate-300 px-2 py-1">
                                @foreach (\App\Models\Verein::orderBy('name')->get() as $v)
                                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-lg bg-slate-700 px-3 py-1.5 text-sm text-white hover:bg-slate-800">+ hinzufügen</button>
                    </form>
                @endif

                @if ($kategorie->typ === 'paar')
                    <div class="mb-4 space-y-2">
                        @forelse ($turnierKategorie->anmeldungenPaar as $a)
                            <div class="flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 p-3">
                                <form method="POST" action="{{ route('admin.turniere.anmeldungen.paar.update', [$turnier, $a]) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="block text-xs text-slate-500">Name Tänzer:in 1</label>
                                        <input type="text" name="name_p1" value="{{ $a->name_p1 }}" class="rounded border border-slate-300 px-2 py-1">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500">Geb. Tänzer:in 1</label>
                                        <input type="date" name="geburtsdatum_p1" value="{{ $a->geburtsdatum_p1->format('Y-m-d') }}" class="rounded border border-slate-300 px-2 py-1">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500">Name Tänzer:in 2</label>
                                        <input type="text" name="name_p2" value="{{ $a->name_p2 }}" class="rounded border border-slate-300 px-2 py-1">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500">Geb. Tänzer:in 2</label>
                                        <input type="date" name="geburtsdatum_p2" value="{{ $a->geburtsdatum_p2->format('Y-m-d') }}" class="rounded border border-slate-300 px-2 py-1">
                                    </div>
                                    <span class="pb-1.5 text-sm text-slate-500">{{ $a->verein->name }}</span>
                                    <button type="submit" class="text-sm text-blue-600 hover:underline">speichern</button>
                                </form>
                                <form method="POST" action="{{ route('admin.turniere.anmeldungen.paar.destroy', [$turnier, $a]) }}" onsubmit="return confirm('Wirklich löschen?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">löschen</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">Keine Anmeldungen.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('admin.turniere.anmeldungen.paar.store', [$turnier, $kategorie]) }}" class="flex flex-wrap items-end gap-2 rounded-lg bg-slate-50 p-3">
                        @csrf
                        <div>
                            <label class="block text-xs text-slate-500">Name Tänzer:in 1</label>
                            <input type="text" name="name_p1" required class="rounded border border-slate-300 px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Geb. Tänzer:in 1</label>
                            <input type="date" name="geburtsdatum_p1" required class="rounded border border-slate-300 px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Name Tänzer:in 2</label>
                            <input type="text" name="name_p2" required class="rounded border border-slate-300 px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Geb. Tänzer:in 2</label>
                            <input type="date" name="geburtsdatum_p2" required class="rounded border border-slate-300 px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Verein</label>
                            <select name="verein_id" required class="rounded border border-slate-300 px-2 py-1">
                                @foreach (\App\Models\Verein::orderBy('name')->get() as $v)
                                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-lg bg-slate-700 px-3 py-1.5 text-sm text-white hover:bg-slate-800">+ hinzufügen</button>
                    </form>
                @endif

                @if ($kategorie->typ === 'formation')
                    <div class="mb-4 space-y-2">
                        @forelse ($turnierKategorie->anmeldungenFormation as $a)
                            <div class="flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 p-3">
                                <form method="POST" action="{{ route('admin.turniere.anmeldungen.formation.update', [$turnier, $a]) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="block text-xs text-slate-500">Name</label>
                                        <input type="text" name="name" value="{{ $a->name }}" class="rounded border border-slate-300 px-2 py-1">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500">Anz. Tänzerinnen</label>
                                        <input type="number" name="anzahl_taenzerinnen" value="{{ $a->anzahl_taenzerinnen }}" min="1" class="w-24 rounded border border-slate-300 px-2 py-1">
                                    </div>
                                    <span class="pb-1.5 text-sm text-slate-500">{{ $a->verein->name }}</span>
                                    <button type="submit" class="text-sm text-blue-600 hover:underline">speichern</button>
                                </form>
                                <form method="POST" action="{{ route('admin.turniere.anmeldungen.formation.destroy', [$turnier, $a]) }}" onsubmit="return confirm('Wirklich löschen?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">löschen</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">Keine Anmeldungen.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('admin.turniere.anmeldungen.formation.store', [$turnier, $kategorie]) }}" class="flex flex-wrap items-end gap-2 rounded-lg bg-slate-50 p-3">
                        @csrf
                        <div>
                            <label class="block text-xs text-slate-500">Name</label>
                            <input type="text" name="name" required class="rounded border border-slate-300 px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Anz. Tänzerinnen</label>
                            <input type="number" name="anzahl_taenzerinnen" min="1" required class="w-24 rounded border border-slate-300 px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Verein</label>
                            <select name="verein_id" required class="rounded border border-slate-300 px-2 py-1">
                                @foreach (\App\Models\Verein::orderBy('name')->get() as $v)
                                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-lg bg-slate-700 px-3 py-1.5 text-sm text-white hover:bg-slate-800">+ hinzufügen</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">
                Diesem Turnier sind noch keine Kategorien zugewiesen. <a href="{{ route('admin.turniere.edit', $turnier) }}" class="text-blue-600 hover:underline">Jetzt zuweisen</a>.
            </p>
        @endforelse
    </div>
</x-layout>
