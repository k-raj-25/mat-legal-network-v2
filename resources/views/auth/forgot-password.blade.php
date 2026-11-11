<x-layouts.auth title="Reset your password" subtitle="Enter your account email and we'll send you a reset link.">
    <div class="card rounded-[28px] p-6 shadow-pop-lg sm:p-8">
        <x-flash/>
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <x-input name="email" type="email" label="Email" required autofocus autocomplete="email"/>
            <button class="btn-primary w-full py-3.5 text-base">Email reset link</button>
        </form>
    </div>
    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Back to login</a></p>
</x-layouts.auth>
