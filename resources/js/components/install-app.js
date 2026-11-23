/**
 * "Install Envydeo" (PWA) support.
 *  - Android / desktop Chrome & Edge: capture `beforeinstallprompt` and show our own Install button.
 *  - iPhone / iPad: there is no install prompt, so we show "Share → Add to Home Screen" instructions.
 *  - Hidden once running as an installed app. "Not now" snoozes the banner for 14 days.
 */
let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault(); // we show our own button instead of Chrome's mini-infobar
    deferredPrompt = event;
    window.dispatchEvent(new CustomEvent('envydeo:installable'));
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    window.dispatchEvent(new CustomEvent('envydeo:installed'));
});

const SNOOZE_KEY = 'envydeo:install-snoozed-at';
const SNOOZE_DAYS = 14;

export const isStandalone = () =>
    window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

export const isIos = () =>
    /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

export default ({ banner = false } = {}) => ({
    canPrompt: !!deferredPrompt,
    ios: isIos() && !isStandalone(),
    standalone: isStandalone(),
    showBanner: false,

    init() {
        window.addEventListener('envydeo:installable', () => {
            this.canPrompt = true;
            if (banner) this.maybeShowBanner();
        });
        window.addEventListener('envydeo:installed', () => {
            this.canPrompt = false;
            this.showBanner = false;
            this.standalone = true;
        });
        if (banner) setTimeout(() => this.maybeShowBanner(), 2500);
    },

    /** Whether there is any way to install right now. */
    get available() {
        return !this.standalone && (this.canPrompt || this.ios);
    },

    maybeShowBanner() {
        if (this.available && !this.snoozed()) this.showBanner = true;
    },

    snoozed() {
        try {
            const at = Number(localStorage.getItem(SNOOZE_KEY));
            return at > 0 && Date.now() - at < SNOOZE_DAYS * 864e5;
        } catch (e) {
            return false;
        }
    },

    dismiss() {
        this.showBanner = false;
        try { localStorage.setItem(SNOOZE_KEY, String(Date.now())); } catch (e) { /* private mode */ }
    },

    async install() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            this.canPrompt = false;
            this.showBanner = false;
            if (outcome !== 'accepted') this.dismiss();
            return;
        }
        if (this.ios) {
            this.showBanner = false;
            window.dispatchEvent(new CustomEvent('envydeo:ios-install-help'));
        }
    },
});
