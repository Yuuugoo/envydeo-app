/**
 * Movie Night swipe deck. Lives inside a Livewire component (`$wire.vote`),
 * or runs standalone with `demo: true` (landing page: likes stay in `likes`).
 * Drag a card (mouse/touch/pen), tap the buttons, or use ← / → keys.
 * Votes are fire-and-forget so swiping never waits on the network; if a vote
 * completes a match we refresh the component to show the match screen.
 */
const THRESHOLD = 110; // px of horizontal drag needed to decide

export default ({ cards = [], genres = {}, persistent = false, demo = false } = {}) => ({
    cards,
    genres,
    persistent, // "Us" rooms keep swiping after a match
    demo, // landing-page demo: no server, likes are kept locally
    likes: [],
    toast: null, // title of a fresh match, shown briefly in "Us" rooms
    index: 0,
    dx: 0,
    dy: 0,
    dragging: false,
    leaving: false,
    snapping: false, // true for one frame while the next card replaces the old one (no slide-back animation)
    _start: null,

    get current() {
        return this.cards[this.index] ?? null;
    },
    get upcoming() {
        return this.cards[this.index + 1] ?? null;
    },
    get remaining() {
        return Math.max(0, this.cards.length - this.index);
    },
    get done() {
        return this.index >= this.cards.length;
    },

    // Visual feedback while dragging
    get cardStyle() {
        const rotate = this.dx / 18;
        return `transform: translate(${this.dx}px, ${this.dy * 0.25}px) rotate(${rotate}deg);`
            + `transition: ${this.dragging || this.snapping ? 'none' : 'transform 260ms cubic-bezier(0.22, 1, 0.36, 1)'};`;
    },
    get likeOpacity() {
        return Math.min(1, Math.max(0, this.dx / THRESHOLD));
    },
    get nopeOpacity() {
        return Math.min(1, Math.max(0, -this.dx / THRESHOLD));
    },
    get nextScale() {
        return 0.94 + 0.06 * Math.min(1, Math.abs(this.dx) / THRESHOLD);
    },

    pointerDown(e) {
        if (this.leaving || !this.current) return;
        this.dragging = true;
        this._start = { x: e.clientX, y: e.clientY };
        e.currentTarget.setPointerCapture?.(e.pointerId);
    },

    pointerMove(e) {
        if (!this.dragging) return;
        this.dx = e.clientX - this._start.x;
        this.dy = e.clientY - this._start.y;
    },

    pointerUp() {
        if (!this.dragging) return;
        this.dragging = false;
        if (Math.abs(this.dx) >= THRESHOLD) {
            this.decide(this.dx > 0);
        } else {
            this.dx = 0;
            this.dy = 0;
        }
    },

    decide(liked) {
        const card = this.current;
        if (!card || this.leaving) return;

        this.leaving = true;
        this.dx = (liked ? 1 : -1) * (window.innerWidth + 200);

        if (this.demo) {
            if (liked) this.likes.push(card);
        } else {
            // Send the vote without blocking the animation.
            this.$wire
                .vote(card.id, liked)
                .then((result) => {
                    if (!result) return;
                    if (result.matched && this.persistent) {
                        this.toast = result.title || 'New match';
                        setTimeout(() => (this.toast = null), 3500);
                    }
                    if (result.refresh) this.$wire.$refresh();
                })
                .catch(() => {});
        }

        setTimeout(() => {
            this.snapping = true;
            this.index++;
            this.dx = 0;
            this.dy = 0;
            requestAnimationFrame(() => requestAnimationFrame(() => {
                this.snapping = false;
                this.leaving = false;
            }));
        }, 260);
    },

    poster(card, size = 'w500') {
        return card?.poster_path ? `https://image.tmdb.org/t/p/${size}${card.poster_path}` : null;
    },

    genreNames(card) {
        return (card?.genre_ids || []).map((id) => this.genres[id]).filter(Boolean).slice(0, 3).join(' • ');
    },
});
