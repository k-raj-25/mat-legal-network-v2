<x-layouts.app title="For Lawyers – Join Free" description="Advocates: create a free, verified MAT LEGAL NETWORK profile and receive client enquiries without exposing your personal number.">
    <section class="bg-brand-950 text-white">
        <div class="container-page grid items-center gap-10 py-16 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold tracking-widest text-gold-300 uppercase">For advocates</p>
                <h1 class="mt-3 text-4xl font-bold sm:text-5xl">Be found by clients who need you.</h1>
                <p class="mt-5 text-lg text-brand-100">Create a free verified profile. Clients discover you by city, practice area and language – and our team passes on genuine enquiries. Your personal number stays private.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-gold px-6 py-3">Register free</a>
                    <a href="{{ route('login') }}" class="btn border border-white/20 px-6 py-3 text-white hover:bg-white/10">Lawyer login</a>
                </div>
            </div>
            <ol class="space-y-4">
                @foreach ([['Register', 'Fill in your professional details and upload your Bar enrolment proof and ID (about 5 minutes).'], ['Get verified', 'Our team checks your details, usually within 2 working days.'], ['Go live', 'Your profile appears in search. We call you when a client asks for you.']] as $i => [$t, $d])
                    <li class="flex gap-4 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-gold-500 font-bold">{{ $i + 1 }}</span>
                        <div><p class="font-semibold">{{ $t }}</p><p class="mt-1 text-sm text-brand-200">{{ $d }}</p></div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
    <section class="container-page grid gap-6 py-14 md:grid-cols-2 lg:grid-cols-4">
        @foreach ([['Free listing', 'No subscription or listing fee.'], ['Privacy protected', 'Your phone and address are never published.'], ['Edit anytime', 'Update your profile whenever you like – changes go live instantly.'], ['BCI-conscious', 'Factual profiles only – no advertising or solicitation.']] as [$t, $d])
            <div class="card p-6"><x-icon name="check-circle" class="size-6 text-emerald-600"/><h2 class="mt-3 font-sans text-lg font-semibold text-slate-900">{{ $t }}</h2><p class="mt-1 text-sm text-slate-600">{{ $d }}</p></div>
        @endforeach
    </section>
</x-layouts.app>
