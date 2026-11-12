import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Clickable table rows: <tr data-row-href="..."> opens the URL when clicked anywhere
 * that is not already an interactive control. Ctrl/Cmd/Shift or middle click opens a
 * new tab, and selecting text does not navigate. Keyboard users use the row's own link.
 */
function rowTarget(e) {
    const row = e.target.closest('tr[data-row-href]');
    if (!row || e.target.closest('a, button, input, select, textarea, label, summary, [role="button"]')) return null;
    if (String(window.getSelection?.() ?? '').length) return null;
    return row;
}
document.addEventListener('click', (e) => {
    const row = e.button === 0 ? rowTarget(e) : null;
    if (!row) return;
    if (e.ctrlKey || e.metaKey || e.shiftKey) window.open(row.dataset.rowHref, '_blank', 'noopener');
    else window.location.assign(row.dataset.rowHref);
});
document.addEventListener('auxclick', (e) => {
    const row = e.button === 1 ? rowTarget(e) : null;
    if (row) window.open(row.dataset.rowHref, '_blank', 'noopener');
});

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

/**
 * Searchable lawyer picker (ARIA 1.2 combobox + listbox) for the admin enquiry page.
 * Results come from the server a page at a time, so it works with any number of lawyers.
 * The selected profile ID lives in a hidden input; the visible input is only for searching.
 */
const PROFILE_ID = /^mln[\s-]*([a-z0-9]{4,16})$/i;
const lawyerLabel = (l) => (l ? `${l.name} · ${l.code}` : '');

Alpine.data('lawyerPicker', ({ url, id, code = '', selected = null, serverError = false }) => ({
    // `selected` is the confirmed choice ({code, name, city, areas}); `code` is what gets submitted.
    selected,
    code: String(code ?? ''),
    query: selected ? lawyerLabel(selected) : String(code ?? ''),
    results: [],
    open: false,
    active: -1,
    loading: false,
    more: false,
    invalid: false,
    failed: false,
    serverError,
    announcement: '',
    timer: null,
    controller: null,
    lastFetched: null,
    init() {
        const form = this.$root.closest('form');
        if (form) form.addEventListener('submit', (e) => this.onSubmit(e));
    },
    showingSelection() { return !!this.selected && this.query === lawyerLabel(this.selected); },
    // When the box still shows the current choice, list everyone rather than searching for it.
    term() { return this.showingSelection() ? '' : this.query.trim(); },
    onInput() {
        this.invalid = false;
        this.serverError = false;
        this.code = this.showingSelection() ? this.selected.code : '';
        this.open = true;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.search(), 200);
    },
    toggle() {
        if (this.open) { this.close(false); return; }
        this.open = true;
        this.search();
    },
    async search() {
        const term = this.term();
        if (term === this.lastFetched) return;
        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;
        if (!this.results.length) this.announcement = 'Searching…';
        try {
            const res = await fetch(url + '?q=' + encodeURIComponent(term), {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            if (!res.ok) throw new Error(String(res.status));
            const data = await res.json();
            this.failed = false;
            let results = data.results;
            // Keep the current choice visible at the top of the unfiltered list.
            if (term === '' && this.selected && !results.some((r) => r.code === this.selected.code)) {
                results = [this.selected, ...results];
            }
            this.results = results;
            this.more = data.more;
            this.lastFetched = term;
            this.active = this.results.findIndex((r) => r.code === this.code);
            if (this.active < 0 && term && this.results.length) this.active = 0;
            const n = this.results.length;
            this.announcement = n
                ? `${n}${this.more ? '+' : ''} lawyer${n === 1 ? '' : 's'} found. Use up and down arrows to choose.`
                : 'No approved lawyer matches.';
        } catch (e) {
            if (e.name === 'AbortError') return;
            this.results = [];
            this.more = false;
            this.failed = true;
            this.lastFetched = null;
            this.announcement = 'Could not load lawyers. Please try again.';
        } finally {
            this.loading = false;
            this.$nextTick(() => this.scrollActive());
        }
    },
    onKeydown(e) {
        const n = this.results.length;
        switch (e.key) {
            case 'ArrowDown':
            case 'ArrowUp':
                e.preventDefault();
                if (!this.open) { this.open = true; this.search(); return; }
                if (!n || e.altKey) return;
                this.active = e.key === 'ArrowDown' ? (this.active + 1) % n : (this.active <= 0 ? n - 1 : this.active - 1);
                this.scrollActive();
                break;
            case 'Home':
            case 'End':
                if (!this.open || !n) return;
                e.preventDefault();
                this.active = e.key === 'Home' ? 0 : n - 1;
                this.scrollActive();
                break;
            case 'Enter':
                // Pick the highlighted lawyer instead of submitting the form.
                if (this.open && this.results[this.active]) {
                    e.preventDefault();
                    this.choose(this.results[this.active]);
                }
                break;
            case 'Tab':
                if (this.open && this.results[this.active] && this.term()) {
                    this.choose(this.results[this.active], false);
                }
                break;
        }
    },
    scrollActive() {
        if (this.active < 0) return;
        document.getElementById(`${id}_opt_${this.active}`)?.scrollIntoView({ block: 'nearest' });
    },
    choose(r, refocus = true) {
        this.selected = r;
        this.code = r.code;
        this.query = lawyerLabel(r);
        this.invalid = false;
        this.serverError = false;
        this.announcement = `${r.name} selected.`;
        this.close(false);
        this.lastFetched = null;
        if (refocus) this.$refs.input.focus();
    },
    clear() {
        this.selected = null;
        this.code = '';
        this.query = '';
        this.invalid = false;
        this.serverError = false;
        this.lastFetched = null;
        this.announcement = 'Assignment cleared. The enquiry will be unassigned when you save.';
        this.close(false);
        this.$refs.input.focus();
    },
    close(restore) {
        this.open = false;
        this.active = -1;
        // Escape with an unfinished search puts the previous choice back.
        if (restore && this.selected && !this.showingSelection()) {
            this.query = lawyerLabel(this.selected);
            this.code = this.selected.code;
            this.lastFetched = null;
        }
    },
    onFocusOut(e) {
        if (this.$root.contains(e.relatedTarget)) return;
        this.close(false);
        this.settle();
    },
    // Decide what the text in the box means once the user leaves it.
    settle() {
        const t = this.query.trim();
        if (this.showingSelection()) { this.code = this.selected.code; return true; }
        if (t === '') { this.code = ''; this.selected = null; return true; }
        // A typed profile ID is still accepted; the server checks it belongs to an approved lawyer.
        const m = t.match(PROFILE_ID);
        if (m) { this.code = 'MLN-' + m[1].toUpperCase(); return true; }
        this.code = '';
        return false;
    },
    onSubmit(e) {
        if (this.settle()) return;
        e.preventDefault();
        this.invalid = true;
        this.open = true;
        this.search();
        this.$refs.input.focus();
    },
}));

/**
 * Small rich text editor for blog posts (contenteditable + a toolbar).
 * The HTML is mirrored into a <textarea name="body">; the server sanitises it again with an allow-list.
 * Without JavaScript the textarea is shown instead.
 */
Alpine.data('richText', () => ({
    ready: false,
    words: 0,
    active: {},
    init() {
        const ed = this.$refs.editor;
        ed.innerHTML = this.$refs.field.value;
        try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) { /* older browsers */ }
        if (!ed.innerHTML.trim()) ed.innerHTML = '<p><br></p>';
        this.ready = true;
        this.count();
        document.addEventListener('selectionchange', () => { if (document.activeElement === ed) this.refresh(); });
        // Keep the textarea current even if the editor is never blurred.
        this.$refs.field.form?.addEventListener('submit', () => this.sync());
    },
    focus() { this.$refs.editor.focus(); },
    sync() {
        const html = this.$refs.editor.innerHTML;
        this.$refs.field.value = /^(<p>(<br>)?<\/p>|<br>)?$/.test(html.trim()) ? '' : html;
        this.count();
    },
    count() {
        const text = this.$refs.editor.innerText || '';
        this.words = (text.trim().match(/\S+/g) || []).length;
    },
    refresh() {
        const block = (document.queryCommandValue('formatBlock') || '').toLowerCase();
        const a = {};
        for (const c of ['bold', 'italic', 'insertUnorderedList', 'insertOrderedList']) {
            try { a[c] = document.queryCommandState(c); } catch (e) { a[c] = false; }
        }
        for (const b of ['p', 'h2', 'h3', 'blockquote']) a[b] = block === b;
        this.active = a;
    },
    run(kind, value) {
        this.focus();
        if (kind === 'block') {
            // Clicking an active heading/quote again turns it back into a paragraph.
            document.execCommand('formatBlock', false, this.active[value] && value !== 'p' ? 'p' : value);
        } else if (kind === 'link') {
            const sel = window.getSelection();
            if (!sel || sel.isCollapsed) { window.alert('Select the words you want to turn into a link first.'); return; }
            const range = sel.getRangeAt(0);
            let url = (window.prompt('Link address (https://…, mailto: or tel:)', 'https://') || '').trim();
            if (!url || url === 'https://') return;
            if (!/^(https?:|mailto:|tel:|\/)/i.test(url)) url = 'https://' + url.replace(/^\/+/, '');
            sel.removeAllRanges(); sel.addRange(range);
            document.execCommand('createLink', false, url);
        } else {
            document.execCommand(value, false, null);
        }
        this.sync();
        this.refresh();
    },
    paste(e) {
        // Paste as plain text so formatting from Word or web pages doesn't come along.
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text/plain');
        const html = text.split(/\r?\n\s*\r?\n/).map((para) => para
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/\r?\n/g, '<br>')).join('</p><p>');
        document.execCommand(text.includes('\n') ? 'insertHTML' : 'insertText', false, text.includes('\n') ? '<p>' + html + '</p>' : text);
        this.sync();
    },
    // Arrow keys move between toolbar buttons (one tab stop for the whole toolbar).
    toolbarKeys(e) {
        const btns = [...this.$root.querySelectorAll('[data-tool]')];
        const i = btns.indexOf(document.activeElement);
        if (i < 0) return;
        let n = null;
        if (e.key === 'ArrowRight') n = (i + 1) % btns.length;
        else if (e.key === 'ArrowLeft') n = (i - 1 + btns.length) % btns.length;
        else if (e.key === 'Home') n = 0;
        else if (e.key === 'End') n = btns.length - 1;
        if (n === null) return;
        e.preventDefault();
        btns[i].tabIndex = -1;
        btns[n].tabIndex = 0;
        btns[n].focus();
    },
}));

Alpine.start();
