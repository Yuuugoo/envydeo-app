/**
 * Turn push notifications on/off for this device.
 * Needs HTTPS (or localhost) and, on iPhone, the app installed to the home screen (iOS 16.4+).
 */
import { isIos, isStandalone } from './install-app';

const toUint8 = (base64) => {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(padded), (c) => c.charCodeAt(0));
};

export default ({ vapidKey = '' } = {}) => ({
    supported: 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window,
    needsInstall: isIos() && !isStandalone(),
    permission: 'Notification' in window ? Notification.permission : 'denied',
    subscribed: false,
    busy: false,
    error: '',

    async init() {
        if (!this.supported) return;
        const reg = await navigator.serviceWorker.ready;
        this.subscribed = Boolean(await reg.pushManager.getSubscription());
    },

    async enable() {
        this.error = '';
        this.busy = true;
        try {
            this.permission = await Notification.requestPermission();
            if (this.permission !== 'granted') {
                this.error = 'Notifications are blocked. Allow them in your browser settings for this site.';
                return;
            }
            const reg = await navigator.serviceWorker.ready;
            const sub = (await reg.pushManager.getSubscription())
                || (await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: toUint8(vapidKey) }));
            const json = sub.toJSON();
            await window.axios.post('/push-subscriptions', {
                endpoint: json.endpoint,
                keys: json.keys,
                contentEncoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0],
            });
            this.subscribed = true;
        } catch (e) {
            console.error(e);
            this.error = 'Couldn’t turn on notifications on this device.';
        } finally {
            this.busy = false;
        }
    },

    async disable() {
        this.busy = true;
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) {
                await window.axios.delete('/push-subscriptions', { data: { endpoint: sub.endpoint } });
                await sub.unsubscribe();
            }
            this.subscribed = false;
        } finally {
            this.busy = false;
        }
    },
});
