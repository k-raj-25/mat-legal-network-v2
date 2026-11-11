<x-layouts.auth title="Log in" subtitle="For registered lawyers and the MAT team.">
    <div class="card p-6 sm:p-8">
        <x-flash/>
        <form method="POST" action="{{ route('login') }}" class="space-y-5" data-testid="login-form">
            @csrf
            <x-input name="email" type="email" label="Email" required autofocus autocomplete="username"/>
            <div>
                <x-input name="password" type="password" label="Password" required autocomplete="current-password"/>
                <a href="{{ route('password.request') }}" class="mt-2 inline-block text-sm font-medium text-brand-700 hover:underline">Forgot password?</a>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="form-check"> Keep me logged in</label>
            <button class="btn-primary w-full py-3">Log in</button>
        </form>
    </div>
    <p class="mt-6 text-center text-sm text-slate-600">Advocate and not registered yet? <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Join the network – free</a></p>
</x-layouts.auth>
