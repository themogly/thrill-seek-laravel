@php
    $items = [
        ['route' => 'account.dashboard', 'label' => 'Dashboard'],
        ['route' => 'account.bookings', 'label' => 'My bookings'],
        ['route' => 'account.payments', 'label' => 'Payments'],
        ['route' => 'account.messages', 'label' => 'Messages'],
    ];
@endphp
<nav aria-label="Account" class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b-2 border-border pb-3">
    @foreach ($items as $item)
        @if (Route::has($item['route']))
            <a href="{{ route($item['route']) }}"
               class="border-b-2 pb-1 text-sm font-bold uppercase tracking-widest transition-colors hover:text-primary-strong {{ request()->routeIs($item['route']) ? 'border-primary text-primary-strong' : 'border-transparent text-secondary' }}">
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
    <form method="POST" action="{{ route('account.logout') }}" class="ml-auto">
        @csrf
        <button type="submit" class="text-sm font-bold uppercase tracking-widest text-muted-foreground transition-colors hover:text-primary-strong">Log out</button>
    </form>
</nav>
