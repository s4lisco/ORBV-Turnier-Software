<x-layout>
    <x-orbv-header />
    <div class="flex min-h-[calc(100vh-73px)] flex-col items-center justify-center gap-8 p-6">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-slate-800">ORBV Turnier-Anmeldung</h1>
            <p class="mt-2 text-slate-500">Ostschweizer Rock'n'Roll und Boogie Woogie Verband</p>
        </div>
        <div class="grid w-full max-w-2xl grid-cols-1 gap-6 sm:grid-cols-2">
            <a href="{{ route('verein.login') }}"
               class="flex flex-col items-center justify-center gap-3 rounded-2xl bg-blue-600 p-10 text-center text-white shadow-lg transition hover:bg-blue-700">
                <span class="text-4xl">🏛️</span>
                <span class="text-xl font-semibold">Verein-Login</span>
            </a>
            <a href="{{ route('admin.login') }}"
               class="flex flex-col items-center justify-center gap-3 rounded-2xl bg-slate-700 p-10 text-center text-white shadow-lg transition hover:bg-slate-800">
                <span class="text-4xl">🗂️</span>
                <span class="text-xl font-semibold">Administration</span>
            </a>
        </div>
        <a href="{{ route('ergebnisse.index') }}" class="text-sm text-blue-600 hover:underline">Ergebnisse ansehen</a>
    </div>
</x-layout>
