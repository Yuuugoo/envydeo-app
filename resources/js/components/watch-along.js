/**
 * Watch Along live screen.
 *
 * The server keeps one shared clock: position = baseMs (+ now − resumedAtMs while playing).
 * We poll /state every ~1.5s, estimate the offset between this device's clock and the
 * server's (using the round trip with the smallest delay), and tick the display locally.
 * A resumedAtMs in the future is the countdown: everyone presses play when it hits 0.
 * Nothing is streamed: people watch on their own services.
 */
const POLL_MS = 1500;

export default ({ urls, initial, code }) => ({
    urls,
    code,
    status: initial.status,
    baseMs: initial.baseMs,
    resumedAtMs: initial.resumedAtMs,
    runtime: initial.runtime,
    members: initial.members,
    reactions: initial.reactions,
    lastEvent: initial.lastEvent,
    lastEventAt: initial.lastEventAt,
    after: initial.reactions.length ? initial.reactions[initial.reactions.length - 1].id : 0,

    offset: initial.serverNow - Date.now(), // server clock − device clock
    bestRtt: Infinity,
    now: Date.now(),
    nudgeMs: 0, // personal "my stream is a bit ahead/behind" correction (display only)
    floaters: [],
    text: '',
    busy: false,
    toast: null,
    _toastTimer: null,
    _lastBeep: null,
    _wakeLock: null,

    init() {
        try { this.nudgeMs = parseInt(localStorage.getItem(`wa-nudge-${this.code}`) || '0', 10) || 0; } catch (e) { /* storage blocked */ }
        this.showEvent(initial.lastEvent, initial.lastEventAt, true);

        setInterval(() => { this.now = Date.now(); this.tickCountdown(); }, 200);
        const poll = () => { if (!document.hidden) this.poll(); };
        setInterval(poll, POLL_MS);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) { this.poll(); this.keepAwake(); } });
        this.keepAwake();
    },

    // ---------------------------------------------------------------- clock
    get serverNow() {
        return this.now + this.offset;
    },
    get positionMs() {
        const raw = this.status === 'playing' && this.resumedAtMs !== null
            ? this.baseMs + (this.serverNow - this.resumedAtMs)
            : this.baseMs;
        return raw + (raw >= 0 ? this.nudgeMs : 0);
    },
    get countdown() {
        if (this.status !== 'playing' || this.resumedAtMs === null) return null;
        const left = this.resumedAtMs - this.serverNow;
        return left > 0 ? Math.ceil(left / 1000) : null;
    },
    get clock() {
        return this.format(Math.max(0, Math.floor(this.positionMs / 1000)));
    },
    get progress() {
        return Math.min(100, Math.max(0, (this.positionMs / 1000 / this.runtime) * 100));
    },
    get here() {
        return this.members.filter((m) => m.here).length;
    },
    get isHost() {
        return this.members.some((m) => m.me && m.host);
    },
    get feed() {
        return this.reactions.slice(-30).reverse();
    },

    format(s) {
        s = Math.max(0, Math.floor(s));
        const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
        const pad = (n) => String(n).padStart(2, '0');
        return h ? `${h}:${pad(m)}:${pad(sec)}` : `${m}:${pad(sec)}`;
    },

    /** True for ~2.5s right after the countdown hits 0: "PRESS PLAY". */
    get pressPlay() {
        if (this.status !== 'playing' || this.resumedAtMs === null) return false;
        const since = this.serverNow - this.resumedAtMs;
        return since >= 0 && since < 2500;
    },

    tickCountdown() {
        // Short buzz on each countdown number, a double buzz on "press play".
        const c = this.countdown;
        if (c === this._lastBeep) return;
        try {
            if (c !== null) navigator.vibrate?.(40);
            else if (this._lastBeep !== null && this.status === 'playing') navigator.vibrate?.([120, 60, 120]);
        } catch (e) { /* unsupported */ }
        this._lastBeep = c;
    },

    nudge(seconds) {
        this.nudgeMs += seconds * 1000;
        try { localStorage.setItem(`wa-nudge-${this.code}`, String(this.nudgeMs)); } catch (e) { /* storage blocked */ }
    },

    // ---------------------------------------------------------------- server sync
    async poll() {
        const t0 = Date.now();
        try {
            const { data } = await window.axios.get(this.urls.state, { params: { after: this.after } });
            this.apply(data, t0, Date.now());
        } catch (e) { /* offline for a moment: keep ticking locally */ }
    },

    apply(data, t0, t1) {
        // Prefer the sample with the smallest round trip: it best estimates the true offset.
        const rtt = t1 - t0;
        if (rtt <= this.bestRtt * 1.5 || rtt < 400) {
            this.bestRtt = Math.min(this.bestRtt, rtt);
            this.offset = data.serverNow - (t0 + rtt / 2);
        }
        if (data.status === 'ended' && this.status !== 'ended') {
            window.location.reload();
            return;
        }
        this.status = data.status;
        this.baseMs = data.baseMs;
        this.resumedAtMs = data.resumedAtMs;
        this.runtime = data.runtime;
        this.members = data.members;
        this.showEvent(data.lastEvent, data.lastEventAt);
        this.addReactions(data.reactions);
        this.keepAwake();
    },

    addReactions(list) {
        for (const r of list) {
            if (r.id <= this.after && this.reactions.some((x) => x.id === r.id)) continue;
            if (!this.reactions.some((x) => x.id === r.id)) {
                this.reactions.push(r);
                if (!r.mine) this.float(r);
            }
            this.after = Math.max(this.after, r.id);
        }
        if (this.reactions.length > 200) this.reactions.splice(0, this.reactions.length - 200);
    },

    showEvent(text, at, quiet = false) {
        if (!text || at === this.lastEventAt && !quiet) return;
        this.lastEvent = text;
        this.lastEventAt = at;
        if (quiet && this.serverNow - at > 8000) return;
        this.toast = text;
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => (this.toast = null), 4000);
    },

    float(r) {
        const id = `${r.id}-${Math.random()}`;
        this.floaters.push({ id, emoji: r.kind === 'text' ? '💬' : r.emoji, name: r.name.split(' ')[0], body: r.body, x: 10 + Math.random() * 70 });
        setTimeout(() => (this.floaters = this.floaters.filter((f) => f.id !== id)), 3200);
    },

    // ---------------------------------------------------------------- actions
    async react(kind, emoji) {
        if (!['playing', 'paused'].includes(this.status)) return;
        this.float({ id: `me-${Date.now()}`, kind, emoji, name: 'You', body: null });
        try { navigator.vibrate?.(15); } catch (e) { /* unsupported */ }
        try {
            const { data } = await window.axios.post(this.urls.react, { kind });
            this.addReactions([data]);
        } catch (e) {
            if (e.response?.status === 429) this.flash('Easy there! Too many reactions at once.');
        }
    },

    async say() {
        const body = this.text.trim();
        if (!body || !['playing', 'paused'].includes(this.status)) return;
        this.text = '';
        try {
            const { data } = await window.axios.post(this.urls.react, { kind: 'text', body });
            this.addReactions([data]);
        } catch (e) {
            this.text = body;
            this.flash(e.response?.status === 429 ? 'Slow down a little.' : 'Couldn’t send that. Try again.');
        }
    },

    async control(action, seconds = null) {
        if (this.busy) return;
        if (action === 'end' && !confirm('End the Watch Along for everyone?')) return;
        this.busy = true;
        const t0 = Date.now();
        try {
            const { data } = await window.axios.post(this.urls.control, { action, seconds, after: this.after });
            this.apply(data, t0, Date.now());
        } catch (e) {
            this.flash('That didn’t work. Try again.');
        } finally {
            this.busy = false;
        }
    },

    flash(text) {
        this.toast = text;
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => (this.toast = null), 3000);
    },

    // Keep the phone screen on while the movie is running (where supported).
    async keepAwake() {
        try {
            if (this.status === 'playing' && !this._wakeLock && 'wakeLock' in navigator && !document.hidden) {
                this._wakeLock = await navigator.wakeLock.request('screen');
                this._wakeLock.addEventListener('release', () => (this._wakeLock = null));
            } else if (this.status !== 'playing' && this._wakeLock) {
                await this._wakeLock.release();
                this._wakeLock = null;
            }
        } catch (e) { /* not allowed right now */ }
    },
});
