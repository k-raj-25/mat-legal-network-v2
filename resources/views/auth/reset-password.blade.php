<x-layouts.auth title="Choose a new password">
    <div class="card p-6 sm:p-8">
        <x-flash/>
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-input name="email" type="email" label="Email" required :value="$email" autocomplete="username"/>
            <x-input name="password" type="password" label="New password" required autocomplete="new-password" help="At least 8 characters with letters and numbers."/>
            <x-input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password"/>
            <button class="btn-primary w-full py-3">Reset password</button>
        </form>
    </div>
</x-layouts.auth>
