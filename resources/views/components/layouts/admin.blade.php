@props(['title'])
@php
    $nav = [
        ['admin.dashboard', 'Dashboard', 'home', null],
        ['admin.lawyers.index', 'Lawyers', 'users', \App\Models\LawyerProfile::where('status', 'pending')->count()],
        ['admin.enquiries.index', 'Enquiries', 'inbox', \App\Models\Enquiry::where('status', 'new')->count()],
        ['admin.changes.index', 'Profile changes', 'list', \App\Models\ProfileChangeLog::whereNull('reviewed_at')->count()],
        ['admin.masters.index', 'Lists & filters', 'filter', null],
        ['admin.settings.edit', 'Settings', 'cog', null],
        ['admin.audit', 'Audit log', 'shield', null],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · Admin · MAT LEGAL NETWORK</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50" x-data="{ nav: false }">
<div class="lg:grid lg:min-h-screen lg:grid-cols-[250px_1fr]">
    <div class="fixed inset-0 z-40 bg-ink/50 backdrop-blur-sm lg:hidden" x-show="nav" x-cloak @click="nav = false"></div>
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col overflow-hidden bg-ink text-slate-300 transition-transform lg:static lg:w-auto lg:translate-x-0" :class="nav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="pointer-events-none absolute -bottom-24 -left-24 size-72 rounded-full bg-brand-500/20 blur-3xl"></div>
        <div class="relative flex h-[72px] items-center px-5"><a href="{{ route('admin.dashboard') }}"><x-logo light/></a></div>
        <nav class="relative flex-1 space-y-1 px-3 py-4" aria-label="Admin">
            @foreach ($nav as [$route, $label, $icon, $count])
                @php $active = request()->routeIs($route) || (substr_count($route, '.') >= 2 && request()->routeIs(Str::beforeLast($route, '.').'.*')); @endphp
                <a href="{{ route($route) }}" class="flex items-center justify-between rounded-2xl px-3 py-2.5 text-sm font-bold transition {{ $active ? 'bg-white text-ink' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span class="flex items-center gap-3"><x-icon :name="$icon" class="size-5"/> {{ $label }}</span>
                    @if ($count)<span class="rounded-full bg-coral-600 px-2 py-0.5 text-xs font-bold text-white">{{ $count }}</span>@endif
                </a>
            @endforeach
        </nav>
        <div class="relative border-t border-white/10 p-4 text-sm">
            <p class="truncate text-white">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('home') }}" target="_blank" class="btn btn-sm bg-white/10 text-white hover:bg-white/20">View site</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm bg-white/10 text-white hover:bg-white/20">Log out</button></form>
            </div>
        </div>
    </aside>

    <div class="min-w-0">
        <header class="sticky top-0 z-30 flex h-[72px] items-center gap-3 border-b border-slate-200/70 bg-white/85 px-4 backdrop-blur-xl sm:px-6">
            <button type="button" class="tile size-10 bg-slate-100 text-ink lg:hidden" @click="nav = true" aria-label="Open menu"><x-icon name="menu" class="size-5"/></button>
            <h1 class="truncate text-xl sm:text-2xl">{{ $title }}</h1>
            <div class="ml-auto">{{ $actions ?? '' }}</div>
        </header>
        <main class="p-4 sm:p-6 lg:p-8">
            <x-flash/>
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
