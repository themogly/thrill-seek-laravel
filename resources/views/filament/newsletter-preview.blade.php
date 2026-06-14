{{-- Rendered newsletter shown at desktop and phone widths (admin UI — flexbox is
     fine here; the email HTML inside the iframes is the email-safe output). Each
     iframe's width is the email's viewport, so the responsive shell reflows to fit
     the phone frame instead of scrolling sideways. --}}
<div class="flex flex-wrap items-start justify-center gap-8">
    <div class="text-center">
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-gray-500">Desktop</p>
        <iframe srcdoc="{{ $html }}" style="width: 600px; max-width: 100%; height: 680px; border: 1px solid #e4e4e7; background: #fff;" title="Desktop preview"></iframe>
    </div>
    <div class="text-center">
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-gray-500">Mobile</p>
        {{-- Fixed phone frame; overflow clipped so a stray wide element can never
             scroll the preview sideways. --}}
        <div style="width: 375px; max-width: 100%; overflow: hidden; border: 1px solid #e4e4e7; border-radius: 28px; background: #fff;">
            <iframe srcdoc="{{ $html }}" style="display: block; width: 375px; height: 680px; border: 0; background: #fff;" title="Mobile preview"></iframe>
        </div>
    </div>
</div>
