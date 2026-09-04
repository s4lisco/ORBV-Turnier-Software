@php
    $einstellung = \App\Models\Einstellung::aktuell();
@endphp
<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-3xl flex-wrap items-center gap-4 p-4">
        @if ($einstellung->logoUrl())
            <img src="{{ $einstellung->logoUrl() }}" alt="ORBV-Logo" class="h-20 w-20 shrink-0 object-contain">
        @else
            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-500"
                 role="img" aria-label="ORBV-Logo (Platzhalter)">
                ORBV
            </div>
        @endif
        <p class="text-sm text-slate-600">
            {{ $einstellung->info_text ?: "Ostschweizer Rock'n'Roll und Boogie Woogie Verband" }}
        </p>
    </div>
</header>
