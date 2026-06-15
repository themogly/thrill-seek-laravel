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

    // Branded date picker (<x-ui.date-field>). The native <input type="date"> stays
    // the single source of truth (value carrier + accessible mobile control); on a
    // fine-pointer desktop this overlays a styled, keyboard-accessible calendar that
    // writes YYYY-MM-DD back to the native input — so the SUBMITTED value never changes.
    Alpine.data('dateField', () => ({
        open: false,
        enhanced: false,
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth(),
        selected: '',
        focusDay: null,

        init() {
            this.enhanced = window.matchMedia('(pointer: fine) and (min-width: 1024px)').matches;
            this.selected = this.native.value || '';
            this.setView();
            // Stay in sync if Livewire morphs the native value (e.g. a validation re-render).
            this.native.addEventListener('input', () => { this.selected = this.native.value || ''; });
        },

        get native() { return this.$refs.native; },
        get min() { return this.native.min || null; },
        get max() { return this.native.max || null; },

        pad(n) { return String(n).padStart(2, '0'); },
        iso(y, m, d) { return `${y}-${this.pad(m + 1)}-${this.pad(d)}`; },
        parse(s) { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); },

        setView() {
            const base = this.selected ? this.parse(this.selected) : (this.max ? this.parse(this.max) : new Date());
            this.viewYear = base.getFullYear();
            this.viewMonth = base.getMonth();
        },

        get display() {
            if (!this.selected) return '';
            return this.parse(this.selected).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
        },
        get months() {
            return Array.from({ length: 12 }, (_, m) => new Date(2000, m, 1).toLocaleDateString('en-GB', { month: 'long' }));
        },
        get years() {
            const now = new Date().getFullYear();
            const start = this.min ? this.parse(this.min).getFullYear() : now - 120;
            const end = this.max ? this.parse(this.max).getFullYear() : now + 2;
            const out = [];
            for (let y = end; y >= start; y--) out.push(y);
            return out;
        },
        get grid() {
            const startDow = (new Date(this.viewYear, this.viewMonth, 1).getDay() + 6) % 7; // Mon-first
            const days = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            const cells = [];
            for (let i = 0; i < startDow; i++) cells.push(null);
            for (let d = 1; d <= days; d++) cells.push(d);
            return cells;
        },

        disabled(d) {
            if (d == null) return true;
            const iso = this.iso(this.viewYear, this.viewMonth, d);
            return (this.min && iso < this.min) || (this.max && iso > this.max);
        },
        isSelected(d) { return d != null && this.iso(this.viewYear, this.viewMonth, d) === this.selected; },
        isToday(d) {
            const t = new Date();
            return d != null && this.viewYear === t.getFullYear() && this.viewMonth === t.getMonth() && d === t.getDate();
        },

        prevMonth() { this.viewMonth === 0 ? (this.viewMonth = 11, this.viewYear--) : this.viewMonth--; },
        nextMonth() { this.viewMonth === 11 ? (this.viewMonth = 0, this.viewYear++) : this.viewMonth++; },

        toggle() { this.open ? this.close() : this.openCal(); },
        openCal() {
            this.open = true;
            this.setView();
            this.focusDay = this.selected ? this.parse(this.selected).getDate() : 1;
            this.$nextTick(() => this.focusGrid());
        },
        close() { this.open = false; this.$nextTick(() => this.$refs.trigger?.focus()); },
        focusGrid() { this.$refs.cal?.querySelector('[data-focus="true"]')?.focus(); },

        pick(d) {
            if (this.disabled(d)) return;
            this.selected = this.iso(this.viewYear, this.viewMonth, d);
            this.native.value = this.selected;
            this.native.dispatchEvent(new Event('input', { bubbles: true }));
            this.native.dispatchEvent(new Event('change', { bubbles: true }));
            this.close();
        },

        onGridKey(e) {
            const moves = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
            if (e.key in moves) { e.preventDefault(); this.moveFocus(moves[e.key]); }
            else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (this.focusDay) this.pick(this.focusDay); }
            else if (e.key === 'PageUp') { e.preventDefault(); this.prevMonth(); this.clampFocus(); this.$nextTick(() => this.focusGrid()); }
            else if (e.key === 'PageDown') { e.preventDefault(); this.nextMonth(); this.clampFocus(); this.$nextTick(() => this.focusGrid()); }
        },
        clampFocus() {
            const days = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            if (this.focusDay > days) this.focusDay = days;
        },
        moveFocus(delta) {
            let d = (this.focusDay || 1) + delta;
            let days = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            if (d < 1) { this.prevMonth(); d = new Date(this.viewYear, this.viewMonth + 1, 0).getDate() + d; }
            else if (d > days) { d -= days; this.nextMonth(); }
            this.focusDay = d;
            this.$nextTick(() => this.focusGrid());
        },
    }));
});
