<x-layouts.guest title="Log in">
    <div class="w-full max-w-[410px] rounded-xl border border-neutral-300 bg-white p-7 shadow-md sm:p-8">
        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-brand-700">Welcome back</p>

        <h2 class="mt-2 text-[22px] font-bold tracking-tight text-neutral-900">Log in to your account</h2>
        <p class="mt-2 text-[13px] text-neutral-600">Use your work email and password to continue.</p>

        <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
            @csrf

            <div class="space-y-1.5">
                <x-form.label for="email" :required="true">Email address</x-form.label>
                <x-form.input
                    id="email"
                    name="email"
                    type="email"
                    :value="old('email')"
                    autocomplete="email"
                    placeholder="you@company.com"
                    :invalid="$errors->has('email')"
                    required
                    autofocus
                />
                <x-form.error :messages="$errors->get('email')" />
            </div>

            <div class="space-y-1.5">
                <x-form.label for="password" :required="true">Password</x-form.label>
                <x-form.input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    :invalid="$errors->has('password')"
                    required
                />
                <x-form.error :messages="$errors->get('password')" />
            </div>

            <div class="flex items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-[11px] text-neutral-600">
                    <input
                        type="checkbox"
                        name="remember"
                        class="h-3.5 w-3.5 rounded border-neutral-300 accent-brand-700"
                    >
                    Remember me
                </label>

                <span
                    class="cursor-not-allowed text-[11px] font-semibold text-neutral-500"
                    title="Password reset will be available when email delivery is configured (FR-AUTH-04)"
                >
                    Forgot password?
                </span>
            </div>

            <x-ui.button type="submit" class="w-full">Log in</x-ui.button>

            @if (! empty($demoAccounts))
                <div class="pt-1">
                    <div class="flex items-center gap-3">
                        <span class="h-px flex-1 bg-neutral-200"></span>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-neutral-400">Demo accounts</span>
                        <span class="h-px flex-1 bg-neutral-200"></span>
                    </div>
                    <p class="mt-2 text-center text-[11px] text-neutral-500">Development only — click an account to autofill, then log in.</p>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        @foreach ($demoAccounts as $account)
                            <button
                                type="button"
                                data-fill-account
                                data-email="{{ $account['email'] }}"
                                data-password="{{ $account['password'] }}"
                                class="h-9 rounded-md border border-neutral-300 bg-white px-2 text-[11px] font-bold text-neutral-700 transition hover:border-brand-600 hover:bg-brand-50 hover:text-brand-700"
                            >{{ $account['label'] }}</button>
                        @endforeach
                    </div>
                </div>
            @endif
        </form>

        <p class="mt-5 text-center text-[11px] text-neutral-500">Need access help? Contact HR or your portal administrator.</p>
    </div>
</x-layouts.guest>
