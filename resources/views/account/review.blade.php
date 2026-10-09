@extends('layouts.app')

@section('title', 'Leave a review — G-Force Skydiving')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-site.page-hero title="Leave a Review" subtitle="Tell future jumpers what your experience was like." />

    <x-site.section>
        <x-account.nav />

        <div class="mx-auto mt-8 max-w-xl">
            <p class="text-muted-foreground">
                You're reviewing as <strong class="text-ink">{{ $customer->name }}</strong> ({{ $role }}). Your review is
                checked by our team before it appears on the site.
            </p>

            <form method="POST" action="{{ route('account.review.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="rating" class="block text-sm font-bold uppercase tracking-widest text-secondary">Your rating</label>
                    <select id="rating" name="rating" required class="mt-2 w-full border-2 border-border bg-background px-4 py-3 text-ink focus:border-primary focus:outline-none">
                        <option value="5" @selected(old('rating', 5) == 5)>★★★★★ — Excellent</option>
                        <option value="4" @selected(old('rating') == 4)>★★★★ — Great</option>
                        <option value="3" @selected(old('rating') == 3)>★★★ — Good</option>
                        <option value="2" @selected(old('rating') == 2)>★★ — Okay</option>
                        <option value="1" @selected(old('rating') == 1)>★ — Poor</option>
                    </select>
                    @error('rating')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="quote" class="block text-sm font-bold uppercase tracking-widest text-secondary">Your review</label>
                    <textarea id="quote" name="quote" rows="5" required minlength="10" maxlength="1000"
                              class="mt-2 w-full border-2 border-border bg-background px-4 py-3 text-ink focus:border-primary focus:outline-none"
                              placeholder="What made your jump memorable?">{{ old('quote') }}</textarea>
                    @error('quote')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="photo" class="block text-sm font-bold uppercase tracking-widest text-secondary">Photo (optional)</label>
                    <input id="photo" name="photo" type="file" accept="image/*"
                           class="mt-2 w-full border-2 border-border bg-background px-4 py-3 text-ink file:mr-4 file:border-0 file:bg-primary-strong file:px-4 file:py-2 file:font-bold file:uppercase file:tracking-widest file:text-primary-foreground" />
                    @error('photo')<p class="mt-1 text-sm text-destructive">{{ $message }}</p>@enderror
                </div>

                <x-ui.button type="submit">Submit review</x-ui.button>
            </form>
        </div>
    </x-site.section>
@endsection
