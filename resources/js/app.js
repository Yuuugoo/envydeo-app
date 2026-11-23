import './bootstrap';
import posterRow from './components/poster-row';
import heroCarousel from './components/hero-carousel';

// Alpine is bundled and started by Livewire 3 (@livewireScripts), so we must not
// import/start our own copy. This module is deferred and runs before Livewire
// starts Alpine, so registering on `alpine:init` is safe.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('posterRow', posterRow);
    window.Alpine.data('heroCarousel', heroCarousel);
});
