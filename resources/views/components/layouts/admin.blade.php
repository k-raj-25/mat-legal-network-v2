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
    <div class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" x-show="nav" x-cloak @click="nav = false"></div>
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-brand-950 text-brand-100 transition-transform lg:static lg:w-auto lg:translate-x-0" :class="nav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="flex h-16 items-center px-5"><a href="{{ route('admin.dashboard') }}"><x-logo light/></a></div>
        <nav class="flex-1 space-y-1 px-3 py-4" aria-label="Admin">
            @foreach ($nav as [$route, $label, $icon, $count])
                @php $active = request()->routeIs(Str::beforeLast($route, '.').'*') && $route !== 'admin.dashboard' || request()->routeIs($route); @endphp
                <a href="{{ route($route) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium {{ $active ? 'bg-white/10 text-white' : 'text-brand-200 hover:bg-white/5 hover:text-white' }}">
                    <span class="flex items-center gap-3"><x-icon :name="$icon" class="size-5"/> {{ $label }}</span>
                    @if ($count)<span class="rounded-full bg-gold-500 px-2 py-0.5 text-xs font-bold text-white">{{ $count }}</span>@endif
                </a>
            @endforeach
        </nav>
        <div class="border-t border-white/10 p-4 text-sm">
            <p class="truncate text-white">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-brand-300">{{ auth()->user()->email }}</p>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('home') }}" target="_blank" class="btn-sm btn rounded-md bg-white/10 text-white hover:bg-white/20">View site</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-sm btn rounded-md bg-white/10 text-white hover:bg-white/20">Log out</button></form>
            </div>
        </div>
    </aside>

    <div class="min-w-0">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6">
            <button type="button" class="btn-ghost -ml-2 lg:hidden" @click="nav = true" aria-label="Open menu"><x-icon name="menu" class="size-6"/></button>
            <h1 class="truncate font-sans text-lg font-semibold text-slate-900">{{ $title }}</h1>
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
