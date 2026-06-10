{{-- The shared trust cards used on the home and AFF pages; items are managed
     in the admin under Settings → General → Trust badges. The surrounding
     section heading differs per page. --}}
@inject('general', 'App\Settings\GeneralSettings')
<div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($general->trust_items as $item)
        <x-site.trust-item :icon="$item['icon']" :value="$item['value']" :label="$item['label']" />
    @endforeach
</div>
