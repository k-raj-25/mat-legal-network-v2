@php use App\Enums\LawyerStatus; @endphp
<x-layouts.lawyer title="Dashboard">
    @if (session('registered'))
        <div class="relative mb-6 overflow-hidden rounded-[28px] bg-gradient-to-br from-brand-500 to-brand-700 p-6 text-white" role="status">
            <div class="pointer-events-none absolute -right-10 -bottom-16 size-48 rounded-full bg-white/10"></div>
            <p class="font-display text-2xl font-extrabold">Registration submitted 🎉</p>
            <p class="mt-1 max-w-2xl text-brand-50">Thank you for joining MAT LEGAL NETWORK. Our team will verify your details – usually within 2 working days – and email you once your profile is live.</p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card p-6 sm:p-7 lg:col-span-2" data-testid="status-card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="flex items-center gap-3 text-xl"><span class="tile size-10 bg-brand-100 text-brand-700"><x-icon name="shield" class="size-5"/></span> Profile status</h2>
                <x-status-badge :status="$lawyer->status" data-testid="status-badge"/>
            </div>
            @switch($lawyer->status)
                @case(LawyerStatus::Pending)
                    <p class="mt-3 text-slate-600">Your profile is <strong>awaiting verification</strong> by our team. It is not visible to the public yet. You can still update your details while you wait.</p>
                    @break
                @case(LawyerStatus::Approved)
                    <p class="mt-3 text-slate-600">Your profile is <strong>live</strong>. Clients can find you in search, and our team will contact you when someone requests a consultation.</p>
                    <a href="{{ route('lawyers.show', $lawyer->slug) }}" target="_blank" class="btn-outline mt-4"><x-icon name="eye" class="size-4"/> View public profile</a>
                    @break
                @case(LawyerStatus::Rejected)
                    <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                        <p class="font-semibold">Our team requested changes:</p>
                        <p class="mt-1">{{ $lawyer->status_reason }}</p>
                    </div>
                    <a href="{{ route('lawyer.profile.edit') }}" class="btn-primary mt-4">Update &amp; resubmit</a>
                    @break
                @case(LawyerStatus::Suspended)
                    <div class="mt-3 rounded-xl border border-slate-300 bg-slate-50 p-4 text-sm text-slate-800">
                        <p class="font-semibold">Your profile is hidden from the website.</p>
                        <p class="mt-1">{{ $lawyer->status_reason }}</p>
                        <p class="mt-2">Please contact {{ $site['company_email'] }} for help.</p>
                    </div>
                    @break
            @endswitch
            <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Profile ID</dt><dd class="mt-0.5 font-mono font-semibold text-slate-900">{{ $lawyer->public_code }}</dd></div>
                <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Submitted</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $lawyer->submitted_at?->format('d M Y') }}</dd></div>
                <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Profile views</dt><dd class="mt-0.5 font-medium text-slate-900">{{ number_format($lawyer->profile_views) }}</dd></div>
            </dl>
        </section>

        <section class="card p-6 sm:p-7">
            <h2 class="flex items-center gap-3 text-xl"><span class="tile size-10 bg-coral-100 text-coral-700"><x-icon name="bolt" class="size-5"/></span> Profile strength</h2>
            <div class="mt-4 flex items-center gap-3">
                <div class="h-3 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-coral-500" style="width: {{ $completeness }}%"></div></div>
                <span class="font-display text-2xl font-extrabold text-ink">{{ $completeness }}%</span>
            </div>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($checks as $label => $done)
                    <li class="flex items-center gap-2 {{ $done ? 'text-slate-500' : 'text-slate-800' }}">
                        <x-icon :name="$done ? 'check-circle' : 'info'" class="size-4 {{ $done ? 'text-emerald-600' : 'text-coral-600' }}"/> {{ $label }}
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('lawyer.profile.edit') }}" class="btn-outline mt-5 w-full">Improve profile</a>
        </section>
    </div>

    <section class="relative mt-6 overflow-hidden rounded-[28px] bg-ink p-7 text-slate-300">
        <div class="pointer-events-none absolute -top-16 -right-16 size-56 rounded-full bg-coral-500/25 blur-3xl"></div>
        <h2 class="relative text-xl text-white">How you'll receive clients</h2>
        <p class="relative mt-2 max-w-3xl leading-7">Clients never see your phone number or email. They contact MAT LEGAL NETWORK quoting your Profile ID, and our team calls you on <strong class="text-white">{{ $lawyer->phone }}</strong> to pass on the request. Please keep your number up to date.</p>
    </section>
</x-layouts.lawyer>
