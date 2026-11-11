@php use App\Enums\LawyerStatus; $labels = \App\Services\LawyerProfileService::FIELD_LABELS; @endphp
<x-layouts.admin :title="$lawyer->full_name">
    <x-slot:actions>
        @if ($lawyer->isPublished())<a href="{{ route('lawyers.show', $lawyer->slug) }}" target="_blank" class="btn-outline btn-sm"><x-icon name="eye" class="size-4"/> Public profile</a>@endif
    </x-slot:actions>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_360px]">
        <div class="min-w-0 space-y-6">
            <section class="card p-6">
                <div class="flex flex-wrap items-start gap-5">
                    <x-avatar :lawyer="$lawyer" size="size-20" text="text-2xl"/>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2"><x-status-badge :status="$lawyer->status" data-testid="admin-status"/> @if ($lawyer->is_featured)<span class="badge bg-coral-50 text-coral-700 ring-coral-200">Featured</span>@endif @unless ($lawyer->user->is_active)<span class="badge bg-rose-50 text-rose-700 ring-rose-200">Login disabled</span>@endunless</div>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $lawyer->full_name }}</h2>
                        <p class="text-slate-600">{{ $lawyer->designation ?? 'Advocate' }} @if ($lawyer->firm_name)· {{ $lawyer->firm_name }}@endif</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $lawyer->public_code }} · Registered {{ $lawyer->created_at->format('d M Y') }}</p>
                    </div>
                </div>
                @if ($lawyer->status_reason)
                    <p class="mt-5 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700"><strong>Last reason given:</strong> {{ $lawyer->status_reason }}</p>
                @endif
            </section>

            <section class="card">
                <h3 class="border-b border-slate-200 px-6 py-4 text-lg">Private contact &amp; verification details</h3>
                <dl class="grid gap-x-6 gap-y-4 p-6 text-sm sm:grid-cols-2">
                    @foreach ([
                        'Email' => $lawyer->user->email, 'Mobile' => $lawyer->phone, 'Alternate' => $lawyer->alternate_phone,
                        'Enrolment no.' => $lawyer->enrolment_number, 'Bar Council' => $lawyer->bar_council,
                        'Office address' => trim($lawyer->office_address.' '.$lawyer->pincode), 'Last login' => $lawyer->user->last_login_at?->format('d M Y, H:i'),
                    ] as $k => $v)
                        <div><dt class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $k }}</dt><dd class="mt-0.5 break-words text-slate-900">{{ $v ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="card">
                <h3 class="border-b border-slate-200 px-6 py-4 text-lg">Public profile</h3>
                <dl class="grid gap-x-6 gap-y-4 p-6 text-sm sm:grid-cols-2">
                    @foreach ([
                        'Location' => $lawyer->locationLabel(), 'Practising since' => $lawyer->practice_since.' ('.$lawyer->experienceLabel().')',
                        'Gender' => config('mat.genders.'.$lawyer->gender), 'Education' => $lawyer->education,
                        'Practice areas' => $lawyer->practiceAreas->pluck('name')->implode(', '), 'Courts' => $lawyer->courts->pluck('name')->implode(', '),
                        'Languages' => $lawyer->languages->pluck('name')->implode(', '), 'Consultation fee' => $lawyer->consultation_fee !== null ? '₹'.number_format($lawyer->consultation_fee) : null,
                        'Online consultation' => $lawyer->offers_online_consultation ? 'Yes' : 'No', 'Profile views' => number_format($lawyer->profile_views),
                    ] as $k => $v)
                        <div><dt class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $k }}</dt><dd class="mt-0.5 text-slate-900">{{ $v ?: '—' }}</dd></div>
                    @endforeach
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold tracking-wide text-slate-500 uppercase">About</dt><dd class="mt-0.5 whitespace-pre-line text-slate-900">{{ $lawyer->bio ?: '—' }}</dd></div>
                </dl>
            </section>

            <section class="card">
                <h3 class="border-b border-slate-200 px-6 py-4 text-lg">Profile change history</h3>
                @forelse ($lawyer->changeLogs as $log)
                    <div class="border-b border-slate-100 px-6 py-4 last:border-0">
                        <p class="text-xs text-slate-500">{{ $log->created_at->format('d M Y, H:i') }} by {{ $log->user?->name ?? 'system' }} @if (! $log->reviewed_at)<span class="badge ml-1 bg-indigo-50 text-indigo-700 ring-indigo-200">New</span>@endif</p>
                        @include('admin.changes._diff', ['changes' => $log->changes])
                    </div>
                @empty
                    <p class="px-6 py-6 text-sm text-slate-500">No edits since registration.</p>
                @endforelse
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card p-5" x-data="{ action: '{{ $lawyer->status === LawyerStatus::Approved ? 'suspend' : 'approve' }}' }" data-testid="review-panel">
                <h3 class="text-lg">Decision</h3>
                <form method="POST" action="{{ route('admin.lawyers.status', $lawyer) }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid gap-2">
                        @php
                            $actions = match ($lawyer->status) {
                                LawyerStatus::Approved => ['suspend' => 'Suspend (hide from website)'],
                                LawyerStatus::Suspended => ['reinstate' => 'Reinstate (make live again)'],
                                default => ['approve' => 'Approve – publish profile', 'reject' => 'Request changes'],
                            };
                        @endphp
                        @foreach ($actions as $k => $l)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-2xl border-2 border-slate-200 px-3 py-2.5 text-sm font-semibold has-checked:border-ink has-checked:bg-slate-50">
                                <input type="radio" name="action" value="{{ $k }}" x-model="action" class="accent-brand-700"> {{ $l }}
                            </label>
                        @endforeach
                    </div>
                    <div x-show="action === 'reject' || action === 'suspend'" x-cloak>
                        <x-input name="reason" type="textarea" rows="3" label="Reason (emailed to the lawyer)" maxlength="1000" placeholder="e.g. The enrolment certificate is not readable. Please upload a clearer copy."/>
                    </div>
                    <button class="btn w-full py-3 text-white" :class="action === 'suspend' || action === 'reject' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-brand-600 hover:bg-brand-700'" data-testid="review-submit">Confirm</button>
                </form>
            </section>

            <section class="card p-5">
                <h3 class="text-lg">Verification documents</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($lawyer->documents as $doc)
                        <li><a href="{{ route('admin.documents.show', $doc) }}" target="_blank" class="flex items-center gap-2 rounded-2xl border-2 border-slate-100 px-3 py-2.5 transition hover:border-ink">
                            <x-icon name="document" class="size-4 shrink-0 text-brand-600"/>
                            <span class="min-w-0"><span class="block font-medium text-slate-900">{{ $doc->typeLabel() }}</span><span class="block truncate text-xs text-slate-500">{{ $doc->original_name }} · {{ number_format($doc->size / 1024) }} KB · {{ $doc->created_at->format('d M Y') }}</span></span>
                        </a></li>
                    @empty
                        <li class="text-slate-500">No documents uploaded.</li>
                    @endforelse
                </ul>
                <p class="mt-3 text-xs text-slate-500">Verify enrolment on the State Bar Council website before approving.</p>
            </section>

            <section class="card space-y-2 p-5">
                <h3 class="text-lg">More actions</h3>
                @if ($lawyer->isPublished())
                    <form method="POST" action="{{ route('admin.lawyers.featured', $lawyer) }}">@csrf<button class="btn-outline w-full">{{ $lawyer->is_featured ? 'Remove from featured' : 'Feature on home page' }}</button></form>
                @endif
                <form method="POST" action="{{ route('admin.lawyers.account', $lawyer) }}" onsubmit="return confirm('Are you sure?')">@csrf<button class="btn-outline w-full">{{ $lawyer->user->is_active ? 'Disable login' : 'Enable login' }}</button></form>
            </section>

            <section class="card p-5">
                <h3 class="text-lg">Decision history</h3>
                <ol class="mt-3 space-y-3 text-sm">
                    @forelse ($history as $h)
                        <li class="border-l-2 border-slate-200 pl-3"><p class="font-medium text-slate-800">{{ Str::of($h->action)->after('.')->replace('_', ' ')->ucfirst() }}</p><p class="text-xs text-slate-500">{{ $h->created_at->format('d M Y, H:i') }} · {{ $h->user?->name }}</p>@if ($h->meta['reason'] ?? null)<p class="mt-1 text-xs text-slate-600">“{{ $h->meta['reason'] }}”</p>@endif</li>
                    @empty
                        <li class="text-slate-500">No decisions yet.</li>
                    @endforelse
                </ol>
            </section>
        </aside>
    </div>
</x-layouts.admin>
