<x-layout title="Ersteinrichtung – ORBV Turnier">
    <div class="flex min-h-screen items-center justify-center p-6">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg">
            <h1 class="mb-2 text-2xl font-bold text-slate-800">Ersteinrichtung</h1>
            <p class="mb-6 text-sm text-slate-500">Lege den ersten Master-Admin-Zugang an.</p>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.setup.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Benutzername</label>
                    <input type="text" name="username" value="{{ old('username') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">E-Mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Passwort (min. 12 Zeichen, Buchstaben/Zahlen/Sonderzeichen)</label>
                    <input type="password" name="password" required minlength="12"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Passwort bestätigen</label>
                    <input type="password" name="password_confirmation" required minlength="12"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                <button type="submit"
                        class="w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                    Admin-Zugang anlegen
                </button>
            </form>
        </div>
    </div>
</x-layout>
