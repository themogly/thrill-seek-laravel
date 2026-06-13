@inject('general', 'App\Settings\GeneralSettings')
<footer class="band-ink mt-24 border-t-4 border-primary">
    <div class="mx-auto max-w-7xl px-4 py-16 lg:px-8">
        <p class="font-display text-4xl uppercase leading-none tracking-wide text-white md:text-6xl">
            {{ $general->tagline }}
        </p>
    </div>
    <div class="mx-auto grid max-w-7xl gap-10 border-t border-white/15 px-4 py-14 lg:grid-cols-4 lg:px-8">
        <div>
            <div class="flex items-center gap-2">
                <img src="/images/logo.png" alt="{{ $general->site_name }}" class="h-12 w-auto" width="48" height="48" loading="lazy" />
                <span class="font-display text-2xl tracking-wider">G-FORCE</span>
            </div>
            <div class="mt-6 flex gap-3">
                <a href="{{ $general->instagram_url }}" target="_blank" rel="noreferrer" aria-label="Instagram" class="flex h-11 w-11 items-center justify-center border border-white/30 text-white transition-colors hover:border-primary hover:bg-primary">
                    <x-icon name="instagram" class="h-4 w-4" />
                </a>
                <a href="{{ $general->facebook_url }}" target="_blank" rel="noreferrer" aria-label="Facebook" class="flex h-11 w-11 items-center justify-center border border-white/30 text-white transition-colors hover:border-primary hover:bg-primary">
                    <x-icon name="facebook" class="h-4 w-4" />
                </a>
            </div>
        </div>
        <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.25em] text-sky-bright">Explore</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="/tandem" class="transition-colors hover:text-sky-bright">Tandem Skydive</a></li>
                <li><a href="/aff" class="transition-colors hover:text-sky-bright">AFF Course</a></li>
                <li><a href="/coached" class="transition-colors hover:text-sky-bright">Coached Skills</a></li>
                <li><a href="/vouchers" class="transition-colors hover:text-sky-bright">Gift Vouchers</a></li>
                @if ($general->shop_enabled)
                    <li><a href="/shop" class="transition-colors hover:text-sky-bright">Shop</a></li>
                @endif
                <li><a href="/hall-of-fame" class="transition-colors hover:text-sky-bright">Hall of Fame</a></li>
            </ul>
        </div>
        <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.25em] text-sky-bright">Contact</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li class="flex items-center gap-2"><x-icon name="phone" class="h-4 w-4" /> {{ $general->phone }}</li>
                <li class="flex items-center gap-2"><x-icon name="mail" class="h-4 w-4" /><span>{{ $general->email }}</span></li>
            </ul>
        </div>
        <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.25em] text-sky-bright">Legal</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="/privacy" class="transition-colors hover:text-sky-bright">Privacy Policy</a></li>
                <li><a href="/terms" class="transition-colors hover:text-sky-bright">Terms &amp; Conditions</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/15 py-6 text-center text-xs text-white/60">
        {{ $general->footer_copyright }}
    </div>
</footer>
