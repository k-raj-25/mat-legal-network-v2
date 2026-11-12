<section class="container-page mt-10">
    <div class="relative overflow-hidden rounded-[32px] bg-ink p-8 text-slate-300 sm:p-12">
        <div class="pointer-events-none absolute -top-20 -right-20 size-72 rounded-full bg-coral-500/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-16 size-72 rounded-full bg-brand-500/25 blur-3xl"></div>
        <div class="relative grid items-center gap-6 lg:grid-cols-[1fr_auto]">
            <div>
                <h2 class="text-3xl text-white">Need advice on your own matter?</h2>
                <p class="mt-3 max-w-2xl leading-7">Articles explain the law in general terms. For advice on your situation, engage MAT LEGAL NETWORK or speak to a verified advocate through our team.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $site['company_phone']) }}" class="btn-accent"><x-icon name="phone" class="size-4"/> Call {{ $site['company_phone'] }}</a>
                <a href="{{ route('lawyers.index') }}" class="btn bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/15">Find a lawyer</a>
            </div>
        </div>
    </div>
</section>
