<x-layout title="Admin-Login – ORBV Turnier">
    <div class="flex min-h-screen items-center justify-center p-6">
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-lg">
            <h1 class="mb-6 text-2xl font-bold text-slate-800">Administration</h1>

            @if ($errors->any())
                <p class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Benutzername</label>
                    <input type="text" name="username" value="{{ old('username') }}" required autofocus
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Passwort</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                <button type="submit"
                        class="w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                    Anmelden
                </button>
            </form>
        </div>
    </div>
</x-layout>
