<x-mail::message>
# Hi {{ $name }},

Here's your secure link to sign in to your G-Force Skydiving account. It lets you view
your bookings, pay any outstanding balance and see your messages.

<x-mail::button :url="$url">
Sign in to my account
</x-mail::button>

This link works once and expires in 20 minutes. If you didn't request it, you can
safely ignore this email — no one can access your account without it.

Blue skies,
The G-Force Skydiving team
</x-mail::message>
