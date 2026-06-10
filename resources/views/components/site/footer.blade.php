@inject('general', 'App\Settings\GeneralSettings')
<footer class="mt-24 bg-secondary text-secondary-foreground">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 lg:grid-cols-4 lg:px-8">
        <div>
            <div class="flex items-center gap-2">
                <img src="/images/logo.png" alt="{{ $general->site_name }}" class="h-12 w-auto" width="48" height="48" loading="lazy" />
                <span class="font-display text-2xl tracking-wider">G-FORCE</span>
            </div>
            <p class="mt-4 font-display text-lg tracking-wide text-primary">
                {{ $general->tagline }}
            </p>
        </div>
        <div>
            <h3 class="font-display text-lg tracking-wide">Explore</h3>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="/tandem" class="hover:text-primary">Tandem Skydive</a></li>
                <li><a href="/aff" class="hover:text-primary">AFF Course</a></li>
                <li><a href="/coached" class="hover:text-primary">Coached Skills</a></li>
                <li><a href="/vouchers" class="hover:text-primary">Gift Vouchers</a></li>
                <li><a href="/shop" class="hover:text-primary">Shop</a></li>
                <li><a href="/hall-of-fame" class="hover:text-primary">Hall of Fame</a></li>
            </ul>
        </div>
        <div>
            <h3 class="font-display text-lg tracking-wide">Contact</h3>
            <ul class="mt-3 space-y-2 text-sm">
                <li class="flex items-center gap-2"><x-icon name="phone" class="h-4 w-4" /> {{ $general->phone }}</li>
                <li class="flex items-center gap-2"><x-icon name="mail" class="h-4 w-4" /><span>{{ $general->email }}</span></li>
            </ul>
            <div class="mt-4 flex gap-3">
                <a href="{{ $general->instagram_url }}" target="_blank" rel="noreferrer" aria-label="Instagram" class="rounded-full bg-primary p-2 text-primary-foreground hover:scale-110 transition-transform">
                    <x-icon name="instagram" class="h-4 w-4" />
                </a>
                <a href="{{ $general->facebook_url }}" target="_blank" rel="noreferrer" aria-label="Facebook" class="rounded-full bg-primary p-2 text-primary-foreground hover:scale-110 transition-transform">
                    <x-icon name="facebook" class="h-4 w-4" />
                </a>
            </div>
        </div>
        <div>
            <h3 class="font-display text-lg tracking-wide">Legal</h3>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="/privacy" class="hover:text-primary">Privacy Policy</a></li>
                <li><a href="/terms" class="hover:text-primary">Terms &amp; Conditions</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10 py-6 text-center text-xs opacity-80">
        {{ $general->footer_copyright }}
    </div>
</footer>
