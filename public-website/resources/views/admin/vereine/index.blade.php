<x-layout title="Vereine – ORBV Turnier">
    <div class="mx-auto max-w-3xl p-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück zum Dashboard</a>

        <div class="mb-6 mt-2 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-800">Vereine</h1>
            <a href="{{ route('admin.vereine.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                + Neuer Verein
            </a>
        </div>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-emerald-100 px-4 py-2 text-sm text-emerald-700">{{ session('status') }}</p>
        @endif

        <div class="space-y-2">
            @forelse ($vereine as $verein)
                <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow">
                    <div>
                        <div class="font-medium">{{ $verein->name }}</div>
                        <div class="text-sm text-slate-500">{{ $verein->username }} · {{ $verein->email }}</div>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <a href="{{ route('admin.vereine.edit', $verein) }}" class="text-blue-600 hover:underline">bearbeiten</a>
                        <form method="POST" action="{{ route('admin.vereine.destroy', $verein) }}" onsubmit="return confirm('Verein wirklich löschen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">löschen</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="rounded-2xl bg-white p-6 text-center text-slate-500 shadow">Noch keine Vereine angelegt.</p>
            @endforelse
        </div>
    </div>
</x-layout>
