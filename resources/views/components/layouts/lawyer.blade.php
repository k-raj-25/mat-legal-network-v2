@props(['title'])
<x-layouts.app :title="$title" noindex :disclaimer="false">
    <div class="border-b border-slate-200 bg-slate-50">
        <div class="container-page flex flex-wrap items-center justify-between gap-3 py-5">
            <div>
                <p class="text-xs font-semibold tracking-widest text-gold-600 uppercase">Lawyer portal</p>
                <h1 class="text-2xl font-bold text-brand-950">{{ $title }}</h1>
            </div>
            <nav class="flex gap-1 rounded-xl bg-white p-1 shadow-xs ring-1 ring-slate-200" aria-label="Lawyer portal">
                <a href="{{ route('lawyer.dashboard') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('lawyer.dashboard') ? 'bg-brand-800 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Dashboard</a>
                <a href="{{ route('lawyer.profile.edit') }}" class="rounded-lg px-3 py-1.5 text-sm font-medium {{ request()->routeIs('lawyer.profile.*') ? 'bg-brand-800 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Edit profile</a>
            </nav>
        </div>
    </div>
    <div class="container-page py-8">
        <x-flash/>
        {{ $slot }}
    </div>
</x-layouts.app>
