@php $editing = $editor->exists; @endphp
<x-layouts.admin :title="$editing ? $editor->name : 'Add editor'">
    <x-slot:actions>
        <a href="{{ route('admin.editors.index') }}" class="btn-ghost"><x-icon name="chevron-left" class="size-4"/> <span class="max-sm:sr-only">All editors</span></a>
    </x-slot:actions>

    <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
        <section class="card p-5 sm:p-7">
            <h2 class="text-lg">{{ $editing ? 'Account details' : 'New editor account' }}</h2>
            @unless ($editing)<p class="mt-1 text-sm text-slate-600">The editor signs in at <span class="font-semibold text-ink">{{ route('login') }}</span> and can manage only their own blog posts.</p>@endunless
            <form method="POST" action="{{ $editing ? route('admin.editors.update', $editor) : route('admin.editors.store') }}" class="mt-5 space-y-4" data-testid="editor-form">
                @csrf
                @if ($editing) @method('PUT') @endif
                <x-input name="name" label="Name (shown on their posts)" required :value="$editor->name" maxlength="120" autocomplete="off"/>
                <x-input name="email" type="email" label="Email (used to sign in)" required :value="$editor->email" maxlength="190" autocomplete="off"/>
                @unless ($editing)
                    <x-input name="password" type="password" label="Temporary password" required autocomplete="new-password" help="At least 8 characters with letters and numbers. Ask them to change it after signing in."/>
                    <x-input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password"/>
                @endunless
                <button class="btn-primary">{{ $editing ? 'Save details' : 'Create editor' }}</button>
            </form>
        </section>

        @if ($editing)
            <div class="space-y-6">
                <section class="card p-5 sm:p-7">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg">Access</h2>
                        <span class="badge {{ $editor->is_active ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-slate-300' }}" data-testid="editor-status">{{ $editor->is_active ? 'Active' : 'Deactivated' }}</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ $editor->published_count }} published and {{ $editor->posts_count - $editor->published_count }} draft {{ Str::plural('post', $editor->posts_count - $editor->published_count) }}. Last sign-in: {{ $editor->last_login_at?->format('d M Y, H:i') ?? 'never' }}.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.editors.toggle', $editor) }}" @if ($editor->is_active) onsubmit="return confirm('Deactivate this editor? They will be signed out and unable to sign in. Their published posts stay on the blog.')" @endif>@csrf
                            <button class="{{ $editor->is_active ? 'btn-danger' : 'btn-primary' }} btn-sm" data-testid="toggle-editor">{{ $editor->is_active ? 'Deactivate account' : 'Activate account' }}</button>
                        </form>
                        <a href="{{ route('admin.posts.index', ['author' => $editor->id]) }}" class="btn-outline btn-sm">View their posts</a>
                    </div>
                </section>

                <section class="card p-5 sm:p-7">
                    <h2 class="text-lg">Reset password</h2>
                    <p class="mt-1 text-sm text-slate-600">Sets a new password and signs the editor out everywhere. Share it with them securely.</p>
                    <form method="POST" action="{{ route('admin.editors.password', $editor) }}" class="mt-4 space-y-4" data-testid="reset-password-form">
                        @csrf @method('PUT')
                        <x-input name="password" type="password" label="New password" required autocomplete="new-password" id="reset_password" help="At least 8 characters with letters and numbers."/>
                        <x-input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" id="reset_password_confirmation"/>
                        <button class="btn-outline">Reset password</button>
                    </form>
                </section>
            </div>
        @endif
    </div>
</x-layouts.admin>
