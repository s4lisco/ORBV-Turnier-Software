<x-layout title="Neuer Verein – ORBV Turnier">
    <div class="mx-auto max-w-md p-6">
        <a href="{{ route('admin.vereine.index') }}" class="text-sm text-blue-600 hover:underline">&larr; zurück</a>
        <h1 class="mb-6 mt-2 text-2xl font-bold text-slate-800">Neuer Verein</h1>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.vereine.store') }}" class="space-y-4 rounded-2xl bg-white p-6 shadow">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Vereinsname</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">E-Mail</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Benutzername</label>
                <input type="text" name="username" value="{{ old('username') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Passwort (min. 12 Zeichen, Buchstaben/Zahlen/Sonderzeichen)</label>
                <input type="password" name="password" required minlength="12"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                Verein anlegen
            </button>
        </form>
    </div>
</x-layout>
