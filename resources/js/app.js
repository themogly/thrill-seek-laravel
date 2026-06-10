// Alpine.js is provided by Livewire (loaded via @livewireScripts in the layout),
// so it is not imported here. Per the original, all animations are pure Tailwind
// CSS transitions — no Motion One / intersect wiring is needed.

// Entrance reveals (Round 5B). Progressive enhancement: elements opt in with
// data-reveal; the .reveal class is only applied once JS runs (and never when
// the user prefers reduced motion), so content is always visible without it.
const setupReveals = () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const targets = document.querySelectorAll('[data-reveal]:not(.reveal)');
    if (!targets.length) return;

    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            }
        },
        { rootMargin: '0px 0px -8% 0px' },
    );

    for (const el of targets) {
        el.classList.add('reveal');
        observer.observe(el);
    }
};

document.addEventListener('DOMContentLoaded', setupReveals);
// Livewire morphs can introduce new [data-reveal] nodes.
document.addEventListener('livewire:navigated', setupReveals);

// Lightweight toast helper replacing sonner. Dispatches a window event that the
// <x-ui.toaster> Alpine component listens for.
window.toast = {
    push(type, message) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
    },
    success(message) {
        this.push('success', message);
    },
    error(message) {
        this.push('error', message);
    },
    info(message) {
        this.push('info', message);
    },
};

// Alpine components for the forms (registered once Alpine, shipped with Livewire,
// initialises). These replace the React state/sonner handlers verbatim.
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Newsletter signup (home + contact). Validates an email and toasts.
    Alpine.data('newsletterForm', (opts = {}) => ({
        email: '',
        submit() {
            if (!this.email.includes('@')) {
                window.toast.error('Please enter a valid email');
                return;
            }
            window.toast.success(opts.message || "You're in! Welcome to the G-Force list.");
            this.email = '';
        },
    }));

    // Generic enquiry form (tandem, aff). Optional delay shows a "Sending..." state.
    Alpine.data('enquiryForm', (opts = {}) => ({
        submitting: false,
        submit(e) {
            const form = e.target;
            const message = opts.message || "Enquiry sent! We'll be in touch shortly.";
            const finish = () => {
                this.submitting = false;
                window.toast.success(message);
                form.reset();
            };
            if (opts.delay) {
                this.submitting = true;
                setTimeout(finish, opts.delay);
            } else {
                finish();
            }
        },
    }));

    // Accessible select-only combobox (WAI-ARIA APG pattern): keyboard navigation,
    // type-ahead, focus management. Backs <x-ui.select>.
    Alpine.data('selectInput', (config = {}) => ({
        open: false,
        value: '',
        label: '',
        highlighted: -1,
        options: config.options || [],
        placeholder: config.placeholder || 'Select',
        _typeahead: '',
        _typeaheadTimer: null,

        toggle() {
            this.open ? this.close() : this.openList();
        },
        openList() {
            this.open = true;
            this.highlighted = this.options.findIndex((o) => o.value === this.value);
            if (this.highlighted < 0) this.highlighted = 0;
            this.$nextTick(() => this.scrollToHighlighted());
        },
        close() {
            this.open = false;
        },
        reset() {
            this.value = '';
            this.label = '';
            this.highlighted = -1;
            this.open = false;
        },
        select(i) {
            const opt = this.options[i];
            if (!opt) return;
            this.value = opt.value;
            this.label = opt.label;
            this.highlighted = i;
            this.close();
            this.$nextTick(() => this.$refs.trigger?.focus());
        },
        move(delta) {
            if (!this.open) return this.openList();
            const n = this.options.length;
            if (!n) return;
            this.highlighted = (this.highlighted + delta + n) % n;
            this.scrollToHighlighted();
        },
        scrollToHighlighted() {
            if (this.highlighted < 0) return;
            const el = document.getElementById(this.$id('select-option', this.highlighted));
            el?.scrollIntoView({ block: 'nearest' });
        },
        onKeydown(e) {
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.move(1);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    this.move(-1);
                    break;
                case 'Home':
                    if (this.open) {
                        e.preventDefault();
                        this.highlighted = 0;
                        this.scrollToHighlighted();
                    }
                    break;
                case 'End':
                    if (this.open) {
                        e.preventDefault();
                        this.highlighted = this.options.length - 1;
                        this.scrollToHighlighted();
                    }
                    break;
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    if (this.open && this.highlighted >= 0) this.select(this.highlighted);
                    else this.openList();
                    break;
                case 'Escape':
                    if (this.open) {
                        e.preventDefault();
                        this.close();
                    }
                    break;
                case 'Tab':
                    this.close();
                    break;
                default:
                    if (e.key.length === 1 && /\S/.test(e.key)) this.typeahead(e.key);
            }
        },
        typeahead(ch) {
            if (!this.open) this.openList();
            clearTimeout(this._typeaheadTimer);
            this._typeahead += ch.toLowerCase();
            this._typeaheadTimer = setTimeout(() => {
                this._typeahead = '';
            }, 500);
            const idx = this.options.findIndex((o) => o.label.toLowerCase().startsWith(this._typeahead));
            if (idx >= 0) {
                this.highlighted = idx;
                this.scrollToHighlighted();
            }
        },
    }));

    // Contact form with zod-equivalent validation.
    Alpine.data('contactForm', () => ({
        sending: false,
        submit(e) {
            const form = e.target;
            const fd = new FormData(form);
            const name = (fd.get('name') || '').toString().trim();
            const email = (fd.get('email') || '').toString().trim();
            const message = (fd.get('message') || '').toString().trim();
            if (!name) return window.toast.error('Name required');
            if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) return window.toast.error('Invalid email');
            if (!message) return window.toast.error('Message required');
            this.sending = true;
            setTimeout(() => {
                this.sending = false;
                window.toast.success("Message sent! We'll be in touch.");
                form.reset();
            }, 600);
        },
    }));
});
