<x-filament-panels::page.simple>
    <x-slot name="title">
        Verify Email
    </x-slot>

    <div class="space-y-4">
        <p class="text-gray-600">
            {{-- Change this message --}}
            Thank you for registering! We've sent a verification email.
            Please check your inbox and click the link to activate your account.
        </p>

        @if (session('status') == 'verification-link-sent')
        <div class="text-green-600 font-medium">
            A new verification link has been sent — please check your email.
        </div>
        @endif

        <form method="POST" action="{{ url('email/verification-notification') }}">
            @csrf

            <x-filament::button type="submit">
                Resend Verification Email
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page.simple>