/**
 * Endless, swipeable row of posters/backdrops.
 *
 * Endless loop: when there are enough items the list is rendered three times
 * ([A][A][A]) and the scroll position is kept inside the middle copy. After a
 * scroll settles we silently jump by exactly one copy width, which lands on an
 * identical frame, so the user can keep going forward (or backward) forever.
 * It works the same for arrow clicks, trackpads and touch swipes.
 *
 * Config:
 *   url      JSON endpoint to lazy-load (when `items` is not given)
 *   key      property of the JSON response that holds the list (default "movies")
 *   items    preloaded items (skips fetching)
 *   type     "movie" | "tv" — used for links when items lack media_type
 *   variant  "poster" | "backdrop"
 *   genres   { id: name } map (overridden by `genres` in the JSON response)
 *   limit    max unique items (default 30)
 */
const IMG = 'https://image.tmdb.org/t/p/';
const MIN_LOOP_ITEMS = 8; // fewer than this never fills a wide screen, so no loop
const MIN_COPY_ITEMS = 16; // pad short lists so one copy is always wider than the viewport

export default (cfg = {}) => ({
    items: [],
    base: [],
    loading: !cfg.items,
    error: false,
    looping: false,
    canScroll: false,
    arrowTop: null,
    genres: cfg.genres || {},
    variant: cfg.variant || 'poster',
    _settle: null,
    _busy: false,
    _measured: false,
    _stride: 0,

    init() {
        if (cfg.items) {
            this.setItems(cfg.items);
        } else {
            const io = new IntersectionObserver(
                (entries) => {
                    if (entries.some((e) => e.isIntersecting)) {
                        io.disconnect();
                        this.load();
                    }
                },
                { rootMargin: '300px 0px' },
            );
            io.observe(this.$el);
        }

        // Rows inside a hidden tab measure as 0px wide; measure again once visible.
        const visible = new IntersectionObserver((entries) => {
            if (entries.some((e) => e.isIntersecting) && !this._measured && this.items.length) {
                this.measure(false);
            }
        });
        visible.observe(this.$el);

        let resizeTimer;
        window.addEventListener(
            'resize',
            () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => this.measure(true), 150);
            },
            { passive: true },
        );
    },

    async load() {
        this.loading = true;
        this.error = false;
        try {
            const res = await fetch(cfg.url, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (data.genres && Object.keys(data.genres).length) this.genres = data.genres;
            this.setItems(data[cfg.key || 'movies'] || []);
        } catch (e) {
            console.error(`Failed to load ${cfg.url}`, e);
            this.error = true;
            this.loading = false;
        }
    },

    setItems(list) {
        const imageKey = this.variant === 'backdrop' ? 'backdrop_path' : 'poster_path';
        const seen = new Set();
        this.items = (list || [])
            .filter((item) => item && item[imageKey] && !seen.has(item.id) && seen.add(item.id))
            .slice(0, cfg.limit || 30);

        this.looping = this.items.length >= MIN_LOOP_ITEMS;
        let base = this.items;
        if (this.looping) {
            while (base.length < MIN_COPY_ITEMS) base = base.concat(this.items);
        }
        this.base = base;
        this.loading = false;
        this.$nextTick(() => requestAnimationFrame(() => this.measure(false)));
    },

    /** Items as rendered: three copies when looping. */
    get rendered() {
        const copies = this.looping ? [0, 1, 2] : [1];
        return copies.flatMap((copy) =>
            this.base.map((item, i) => ({ item, key: `${copy}-${i}-${item.id}`, clone: copy !== 1 })),
        );
    },

    // ---- geometry ---------------------------------------------------------
    get track() {
        return this.$refs.track;
    },

    stride() {
        const first = this.track?.querySelector('[data-item]');
        if (!first) return 0;
        const gap = parseFloat(getComputedStyle(this.track).columnGap) || 0;
        return first.getBoundingClientRect().width + gap;
    },

    period() {
        return this.base.length * this.stride();
    },

    pageSize() {
        const stride = this.stride();
        if (!stride) return this.track.clientWidth;
        const pad = parseFloat(getComputedStyle(this.track).paddingLeft) || 0;
        return Math.max(1, Math.floor((this.track.clientWidth - pad * 2 + 4) / stride)) * stride;
    },

    measure(keepPosition) {
        const track = this.track;
        if (!track || !track.clientWidth || !this.stride()) return; // hidden: try again when visible
        this._measured = true;
        const media = track.querySelector('[data-media]');
        this.arrowTop = media ? media.offsetHeight / 2 : null;

        const stride = this.stride();
        if (this.looping) {
            // Keep the same first-visible card across resizes (card widths change, so
            // a pixel offset would land mid-card); always align to a card edge.
            const n = this.base.length;
            const index = keepPosition && this._stride
                ? ((Math.round(track.scrollLeft / this._stride) % n) + n) % n
                : 0;
            this.jumpTo((n + index) * stride);
        }
        this._stride = stride;
        this.canScroll = this.looping || track.scrollWidth > track.clientWidth + 2;
    },

    jumpTo(x) {
        // Instant, no smooth-scroll; lands on an identical frame in another copy.
        this.track.scrollTo({ left: x, behavior: 'instant' });
    },

    // ---- interaction ------------------------------------------------------
    onScroll() {
        clearTimeout(this._settle);
        this._settle = setTimeout(() => this.settle(), 120);
    },

    settle() {
        this._busy = false;
        if (!this.looping) return;
        const p = this.period();
        const x = this.track.scrollLeft;
        if (x < p * 0.5) this.jumpTo(x + p);
        else if (x >= p * 1.5) this.jumpTo(x - p);
    },

    scrollByPage(direction) {
        if (this._busy) return;
        this._busy = true;
        setTimeout(() => (this._busy = false), 600); // safety net if no scroll event fires
        this.track.scrollBy({ left: direction * this.pageSize(), behavior: 'smooth' });
    },

    next() {
        this.scrollByPage(1);
    },

    prev() {
        this.scrollByPage(-1);
    },

    // ---- display helpers --------------------------------------------------
    isTv(item) {
        return item.media_type === 'tv' || (!item.media_type && cfg.type === 'tv');
    },
    link(item) {
        return `${this.isTv(item) ? '/shows/' : '/movies/'}${item.id}`;
    },
    title(item) {
        return item.title || item.name || '';
    },
    year(item) {
        return (item.release_date || item.first_air_date || '').slice(0, 4);
    },
    rating(item) {
        return item.vote_average ? Number(item.vote_average).toFixed(1) : null;
    },
    genreNames(item) {
        const names = item.genre_ids
            ? item.genre_ids.map((id) => this.genres[id])
            : (item.genres || []).map((g) => g.name);
        return names.filter(Boolean).slice(0, 2).join(' • ');
    },
    image(item) {
        return this.variant === 'backdrop'
            ? `${IMG}w780${item.backdrop_path}`
            : `${IMG}w342${item.poster_path}`;
    },
});
