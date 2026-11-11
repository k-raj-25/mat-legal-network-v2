<x-layouts.app title="About Us" description="MAT LEGAL NETWORK helps people across India find verified advocates and connect with them privately through one trusted helpline.">
    <section class="bg-gradient-to-b from-brand-50 to-white">
        <div class="container-page py-16 text-center">
            <h1 class="text-4xl font-bold text-brand-950 sm:text-5xl">Making good legal help easier to find</h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-600">MAT LEGAL NETWORK is a directory of verified advocates. We help people compare lawyers by city, practice area, language and experience – and then connect them through our own team, so nobody's personal number ends up on the internet.</p>
        </div>
    </section>
    <section class="container-page grid gap-6 py-12 md:grid-cols-3">
        @foreach ([
            ['shield', 'Verified, not just listed', 'Every advocate submits Bar enrolment proof and ID. Our team reviews each profile before it goes live.'],
            ['lock', 'Private by design', 'Lawyers\' phone numbers and addresses are never shown. Clients reach us; we connect them with the right lawyer.'],
            ['users', 'Free to join and search', 'Searching is free for clients and listing is free for lawyers while we grow the network across India.'],
        ] as [$icon, $t, $d])
            <div class="card p-6">
                <span class="inline-flex size-11 items-center justify-center rounded-xl bg-brand-900 text-gold-200"><x-icon :name="$icon" class="size-5"/></span>
                <h2 class="mt-4 text-xl font-semibold text-slate-900">{{ $t }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $d }}</p>
            </div>
        @endforeach
    </section>
    <section class="container-page">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-sm leading-6 text-slate-600">
            <h2 class="text-lg font-semibold text-slate-900">What we are – and what we are not</h2>
            <p class="mt-2">MAT LEGAL NETWORK is an information and connection platform. We are not a law firm, we do not give legal advice and we do not recommend or rank lawyers based on outcomes. Any engagement, advice and fee is strictly between you and the advocate you choose. Please read our <a href="{{ route('legal', 'disclaimer') }}" class="font-medium text-brand-700 underline">Disclaimer</a>.</p>
        </div>
    </section>
</x-layouts.app>
