import Alpine from 'alpinejs';
import '../css/main.css';

/* Homepage hero slideshow. Crossfade only, no sliding, matching the
   restrained feel of the rest of the site. Pauses when the tab is hidden
   and respects reduced-motion. */
Alpine.data('mkSlider', (count) => ({
  i: 0,
  timer: null,
  start() {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || count < 2) return;
    this.timer = setInterval(() => this.next(), 6000);
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        clearInterval(this.timer);
      } else if (!this.timer || true) {
        clearInterval(this.timer);
        this.timer = setInterval(() => this.next(), 6000);
      }
    });
  },
  next() {
    this.i = (this.i + 1) % count;
  },
  go(n) {
    this.i = n;
    clearInterval(this.timer);
    this.timer = setInterval(() => this.next(), 6000);
  },
}));

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
  if (window.jQuery) {
    window.jQuery(document.body).on('added_to_cart removed_from_cart', () => {
      document.dispatchEvent(new CustomEvent('mk:cart-updated'));
    });
  }
});
