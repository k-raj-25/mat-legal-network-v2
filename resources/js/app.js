import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Dependent State -> City dropdown.
 * Usage: x-data="cityPicker({ url: '/locations/:state/cities', state: '..', city: '..', key: 'id'|'slug' })"
 */
Alpine.data('cityPicker', ({ url, state = '', city = '', key = 'id', initial = null }) => ({
    state: String(state ?? ''),
    city: String(city ?? ''),
    cities: [],
    loading: false,
    async init() {
        this.$watch('state', () => this.load(false));
        if (initial && initial.length) {
            // Cities were rendered by the server: no extra request needed.
            this.cities = initial;
            this.$nextTick(() => { this.city = String(city ?? ''); });
        } else if (this.state) {
            await this.load(true);
        }
    },
    async load(keepCity) {
        if (!keepCity) this.city = '';
        this.cities = [];
        if (!this.state) return;
        this.loading = true;
        try {
            const res = await fetch(url.replace(':state', encodeURIComponent(this.state)), { headers: { Accept: 'application/json' } });
            this.cities = res.ok ? await res.json() : [];
        } catch (e) {
            this.cities = [];
        } finally {
            this.loading = false;
        }
        // Wait for <option>s to render so the stored value is re-selected.
        this.$nextTick(() => { if (keepCity) this.city = String(city ?? ''); });
    },
    value(c) { return String(c[key]); },
}));

/** Bar Council / BCI disclaimer shown once per visitor (stored in a first-party cookie). */
Alpine.data('disclaimer', () => ({
    open: false,
    init() {
        this.sync();
        // Back/forward cache restores the page as it was; re-check in case it was accepted elsewhere.
        window.addEventListener('pageshow', (e) => { if (e.persisted) this.sync(); });
    },
    sync() {
        this.open = !document.cookie.split('; ').some((c) => c.startsWith('mln_disclaimer=1'));
        document.documentElement.classList.toggle('overflow-hidden', this.open);
    },
    accept() {
        document.cookie = 'mln_disclaimer=1; max-age=' + 60 * 60 * 24 * 180 + '; path=/; SameSite=Lax';
        this.open = false;
        document.documentElement.classList.remove('overflow-hidden');
    },
}));

/** Simple multi-step wizard for the lawyer registration form. */
Alpine.data('wizard', (steps, startAt = 0) => ({
    step: startAt,
    steps,
    next() {
        const panel = this.$refs['step' + this.step];
        const fields = panel ? [...panel.querySelectorAll('input, select, textarea')] : [];
        const invalid = fields.find((f) => !f.checkValidity());
        if (invalid) { invalid.reportValidity(); return; }
        // Checkbox groups that need at least one selection.
        for (const group of panel ? panel.querySelectorAll('[data-required-group]') : []) {
            const msg = group.querySelector('[data-group-error]');
            const ok = group.querySelectorAll('input[type=checkbox]:checked').length > 0;
            if (msg) msg.hidden = ok;
            if (!ok) { group.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
        }
        if (this.step < this.steps.length - 1) { this.step++; this.scrollTop(); }
    },
    prev() { if (this.step > 0) { this.step--; this.scrollTop(); } },
    go(i) { if (i < this.step) { this.step = i; this.scrollTop(); } },
    // Bring the top of the form back into view after a step change, but only
    // scroll *up*: if the form's top is already visible (e.g. the page is still
    // at the top), stay put instead of jumping down past the page title. Land
    // just below the sticky site header so the progress bar isn't hidden.
    scrollTop() {
        this.$nextTick(() => {
            const header = document.querySelector('body > header');
            const offset = (header && getComputedStyle(header).position === 'sticky' ? header.offsetHeight : 0) + 16;
            const top = this.$root.getBoundingClientRect().top;
            if (top >= offset) return;
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: Math.max(0, window.scrollY + top - offset), behavior: reduce ? 'auto' : 'smooth' });
        });
    },
}));

/** Searchable multi-select of checkboxes with a max count. */
Alpine.data('checkList', (max = 10) => ({
    q: '',
    max,
    count: 0,
    init() { this.recount(); },
    recount() { this.count = this.$root.querySelectorAll('input[type=checkbox]:checked').length; },
    matches(label) { return label.toLowerCase().includes(this.q.toLowerCase()); },
}));

Alpine.start();
