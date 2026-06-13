{{-- Rendered newsletter shown at desktop and mobile widths (admin UI — flexbox
     is fine here; the email HTML inside the iframes is the email-safe output). --}}
<div class="flex flex-wrap items-start justify-center gap-6">
    <div class="text-center">
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-gray-500">Desktop</p>
        <iframe srcdoc="{{ $html }}" style="width: 600px; max-width: 100%; height: 640px; border: 1px solid #e4e4e7; background: #fff;" title="Desktop preview"></iframe>
    </div>
    <div class="text-center">
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-gray-500">Mobile</p>
        <iframe srcdoc="{{ $html }}" style="width: 360px; height: 640px; border: 1px solid #e4e4e7; background: #fff;" title="Mobile preview"></iframe>
    </div>
</div>
