@props(['title'])
<x-layouts.app :title="$title" noindex :disclaimer="false">
    <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="dot-grid absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent)]"></div>
            <div class="absolute -top-24 -left-24 size-80 rounded-full bg-brand-300/35 blur-3xl"></div>
            <div class="absolute -top-24 right-0 size-72 rounded-full bg-coral-300/30 blur-3xl"></div>
        </div>
        <div class="container-page flex flex-wrap items-end justify-between gap-4 pt-6 pb-6 sm:pt-10">
            <div>
                <p class="eyebrow"><x-icon name="briefcase" class="size-4 text-coral-500"/> Lawyer portal</p>
                <h1 class="mt-3 text-4xl">{{ $title }}</h1>
            </div>
            <nav class="flex gap-1 rounded-full bg-white p-1 shadow-pop ring-1 ring-slate-200/70" aria-label="Lawyer portal">
                <a href="{{ route('lawyer.dashboard') }}" class="rounded-full px-4 py-2 text-sm font-bold transition {{ request()->routeIs('lawyer.dashboard') ? 'bg-ink text-white' : 'text-slate-600 hover:text-ink' }}">Dashboard</a>
                <a href="{{ route('lawyer.profile.edit') }}" class="rounded-full px-4 py-2 text-sm font-bold transition {{ request()->routeIs('lawyer.profile.*') ? 'bg-ink text-white' : 'text-slate-600 hover:text-ink' }}">Edit profile</a>
            </nav>
        </div>
    </div>
    <div class="container-page pb-8">
        <x-flash/>
        {{ $slot }}
    </div>
</x-layouts.app>
