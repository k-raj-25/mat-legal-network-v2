<x-layouts.admin title="My account">
    <div class="grid max-w-4xl gap-6 md:grid-cols-2">
        <section class="card p-5 sm:p-7">
            <h2 class="text-lg">Your details</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="font-semibold text-slate-500">Name</dt><dd class="font-bold text-ink">{{ auth()->user()->name }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Email</dt><dd class="font-bold break-all text-ink">{{ auth()->user()->email }}</dd></div>
                <div><dt class="font-semibold text-slate-500">Role</dt><dd class="font-bold text-ink">{{ auth()->user()->role->label() }}</dd></div>
            </dl>
            @if (auth()->user()->isEditor())<p class="mt-4 text-sm text-slate-600">To change your name or email, ask the admin.</p>@endif
        </section>
        <section class="card p-5 sm:p-7">
            <h2 class="text-lg">Change password</h2>
            <form method="POST" action="{{ route('admin.account.password') }}" class="mt-4 space-y-4" data-testid="account-password-form">
                @csrf @method('PUT')
                <x-input name="current_password" type="password" label="Current password" required autocomplete="current-password"/>
                <x-input name="password" type="password" label="New password" required autocomplete="new-password" help="At least 8 characters with letters and numbers."/>
                <x-input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password"/>
                <button class="btn-primary">Update password</button>
            </form>
        </section>
    </div>
</x-layouts.admin>
