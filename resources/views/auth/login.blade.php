<x-layouts.auth title="Log in" subtitle="For registered lawyers and the MAT team." art="auth-login">
    <div class="card rounded-[28px] p-6 shadow-pop-lg sm:p-8">
        <x-flash/>
        <form method="POST" action="{{ route('login') }}" class="space-y-5" data-testid="login-form">
            @csrf
            <x-input name="email" type="email" label="Email" required autofocus autocomplete="username"/>
            <div>
                <x-input name="password" type="password" label="Password" required autocomplete="current-password"/>
                <a href="{{ route('password.request') }}" class="mt-2 inline-block text-sm font-medium text-brand-700 hover:underline">Forgot password?</a>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="form-check"> Keep me logged in</label>
            <button class="btn-primary w-full py-3.5 text-base">Log in</button>
        </form>
    </div>
    <div class="mt-6 flex items-center justify-between gap-3 rounded-3xl bg-coral-50 p-4 ring-1 ring-coral-200"><p class="text-sm font-semibold text-ink">Advocate and not registered yet?</p><a href="{{ route('register') }}" class="btn-accent btn-sm shrink-0">Join free</a></div>
</x-layouts.auth>
