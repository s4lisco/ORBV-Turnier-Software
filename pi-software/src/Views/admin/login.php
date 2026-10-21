<div class="flex min-h-screen items-center justify-center p-6">
    <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-lg">
        <h1 class="mb-6 text-2xl font-bold text-slate-800">Wertungsbüro-Login</h1>

        <?php if ($error): ?>
            <p class="mb-4 rounded-lg bg-red-100 px-4 py-2 text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="/admin/login" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Benutzername</label>
                <input type="text" name="username" required
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
