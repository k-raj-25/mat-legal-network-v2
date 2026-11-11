@php use App\Enums\LawyerStatus; @endphp
<x-layouts.lawyer title="Dashboard">
    @if (session('registered'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-900" role="status">
            <p class="font-semibold">Registration submitted 🎉</p>
            <p class="mt-1 text-sm">Thank you for joining MAT LEGAL NETWORK. Our team will verify your details – usually within 2 working days – and email you once your profile is live.</p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card p-6 lg:col-span-2" data-testid="status-card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-sans text-lg font-semibold text-slate-900">Profile status</h2>
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
            <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">Profile ID</dt><dd class="mt-0.5 font-mono font-semibold text-slate-900">{{ $lawyer->public_code }}</dd></div>
                <div><dt class="text-slate-500">Submitted</dt><dd class="mt-0.5 font-medium text-slate-900">{{ $lawyer->submitted_at?->format('d M Y') }}</dd></div>
                <div><dt class="text-slate-500">Profile views</dt><dd class="mt-0.5 font-medium text-slate-900">{{ number_format($lawyer->profile_views) }}</dd></div>
            </dl>
        </section>

        <section class="card p-6">
            <h2 class="font-sans text-lg font-semibold text-slate-900">Profile strength</h2>
            <div class="mt-4 flex items-center gap-3">
                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gold-500" style="width: {{ $completeness }}%"></div></div>
                <span class="text-sm font-semibold text-slate-900">{{ $completeness }}%</span>
            </div>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach ($checks as $label => $done)
                    <li class="flex items-center gap-2 {{ $done ? 'text-slate-500' : 'text-slate-800' }}">
                        <x-icon :name="$done ? 'check-circle' : 'info'" class="size-4 {{ $done ? 'text-emerald-600' : 'text-gold-600' }}"/> {{ $label }}
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('lawyer.profile.edit') }}" class="btn-outline mt-5 w-full">Improve profile</a>
        </section>
    </div>

    <section class="card mt-6 p-6">
        <h2 class="font-sans text-lg font-semibold text-slate-900">How you'll receive clients</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Clients never see your phone number or email. They contact MAT LEGAL NETWORK quoting your Profile ID, and our team calls you on <strong>{{ $lawyer->phone }}</strong> to pass on the request. Please keep your number up to date.</p>
    </section>
</x-layouts.lawyer>
