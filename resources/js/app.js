import './bootstrap';
import posterRow from './components/poster-row';
import heroCarousel from './components/hero-carousel';
import swipeDeck from './components/swipe-deck';
import installApp from './components/install-app';
import pushToggle from './components/push-toggle';

// Alpine is bundled and started by Livewire 3 (@livewireScripts), so we must not
// import/start our own copy. This module is deferred and runs before Livewire
// starts Alpine, so registering on `alpine:init` is safe.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('posterRow', posterRow);
    window.Alpine.data('heroCarousel', heroCarousel);
    window.Alpine.data('swipeDeck', swipeDeck);
    window.Alpine.data('installApp', installApp);
    window.Alpine.data('pushToggle', pushToggle);
});

// Installable app (PWA): offline page, faster repeat visits, and push notifications later.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((error) => console.warn('Service worker registration failed', error));
    });
}
