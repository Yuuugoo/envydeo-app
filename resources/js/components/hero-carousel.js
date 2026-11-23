/**
 * Home page hero: endless cross-fading slideshow with autoplay, arrows,
 * keyboard, swipe, and a shared video modal (player / trailer).
 */
const AUTOPLAY_MS = 5000; // same pace as the original carousel; pauses only while a video is open

export default (count = 0) => ({
    count,
    current: 0,
    paused: false,
    video: null, // URL shown in the modal, or null
    loaded: { 0: true, 1: true }, // slides whose backdrop may load (lazy)
    _timer: null,
    _touchX: null,

    init() {
        this.start();
        document.addEventListener('visibilitychange', () => (document.hidden ? this.stop() : this.start()));
    },

    go(index) {
        this.current = ((index % this.count) + this.count) % this.count;
        this.loaded[this.current] = true;
        this.loaded[(this.current + 1) % this.count] = true;
        this.start(); // restart the autoplay countdown after any manual move
    },

    next() {
        this.go(this.current + 1);
    },

    prev() {
        this.go(this.current - 1);
    },

    start() {
        this.stop();
        if (this.count < 2) return;
        this._timer = setInterval(() => {
            if (!this.paused && !this.video) {
                this.go(this.current + 1);
            }
        }, AUTOPLAY_MS);
    },

    stop() {
        clearInterval(this._timer);
    },

    touchStart(e) {
        this._touchX = e.changedTouches[0].clientX;
    },

    touchEnd(e) {
        if (this._touchX === null) return;
        const dx = e.changedTouches[0].clientX - this._touchX;
        this._touchX = null;
        if (Math.abs(dx) > 50) (dx < 0 ? this.next() : this.prev());
    },

    openVideo(url) {
        this.video = url;
    },

    closeVideo() {
        this.video = null;
    },
});
