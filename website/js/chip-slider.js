/* La Sem — a row of category chips as a slider: arrow buttons that show only when there is more
   to see, faded edges, swipe on touch screens, drag and mouse-wheel scrolling on computers.
   Used by the order page (js/order.js), table ordering (dine-in/assets/dine-in.js) and the awards
   trophy wall: window.chipSlider(element) wraps the scrolling element once; calling it again does nothing.
   With data-autoplay on the element, the row also glides by itself in an endless loop, pausing while
   the mouse is over it, while it has keyboard focus, or for a moment after someone scrolls it. */
(function () {
  'use strict';

  var ARROW = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

  window.chipSlider = function (track) {
    if (!track || track.dataset.slider) return;
    track.dataset.slider = '1';

    var wrap = document.createElement('div');
    wrap.className = 'chip-slider';
    track.parentNode.insertBefore(wrap, track);
    wrap.appendChild(track);

    function button(dir, label) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'chip-slider-btn is-' + dir;
      b.setAttribute('aria-label', label);
      b.tabIndex = -1; // the chips themselves are reachable with the keyboard
      b.innerHTML = ARROW;
      b.addEventListener('click', function () {
        track.scrollBy({ left: (dir === 'prev' ? -1 : 1) * Math.max(160, track.clientWidth * 0.7), behavior: 'smooth' });
      });
      wrap.appendChild(b);
    }
    button('prev', 'Scroll back');
    button('next', 'Scroll forward');

    var looping = track.hasAttribute('data-autoplay') && !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    if (looping) autoplay();

    function update() {
      if (looping) { wrap.classList.add('can-prev', 'can-next'); return; } // an endless row always has more both ways
      var max = track.scrollWidth - track.clientWidth;
      wrap.classList.toggle('can-prev', track.scrollLeft > 2);
      wrap.classList.toggle('can-next', track.scrollLeft < max - 2);
    }
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    if (window.ResizeObserver) new ResizeObserver(update).observe(track);
    update();

    // Mouse wheel scrolls sideways when the row overflows.
    track.addEventListener('wheel', function (e) {
      if (Math.abs(e.deltaY) <= Math.abs(e.deltaX) || track.scrollWidth <= track.clientWidth) return;
      var before = track.scrollLeft;
      track.scrollLeft += e.deltaY;
      if (track.scrollLeft !== before) e.preventDefault();
    }, { passive: false });

    // Drag with the mouse (touch screens already swipe natively). A drag doesn't count as a click.
    var startX = 0, startLeft = 0, down = false, dragged = false;
    track.addEventListener('pointerdown', function (e) {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      down = true; dragged = false; startX = e.clientX; startLeft = track.scrollLeft;
    });
    window.addEventListener('pointermove', function (e) {
      if (!down) return;
      var dx = e.clientX - startX;
      if (!dragged && Math.abs(dx) > 6) { dragged = true; wrap.classList.add('is-dragging'); }
      if (dragged) track.scrollLeft = startLeft - dx;
    });
    window.addEventListener('pointerup', function () {
      if (!down) return;
      down = false;
      wrap.classList.remove('is-dragging');
    });
    track.addEventListener('click', function (e) {
      if (dragged) { e.preventDefault(); e.stopPropagation(); dragged = false; }
    }, true);

    // ---------- endless self-moving row ----------
    function autoplay() {
      var SPEED = 38; // pixels per second
      var items = Array.prototype.slice.call(track.children);
      if (!items.length) return;
      // A second copy of the items follows the first, so the row can loop without a jump.
      items.forEach(function (el) {
        var c = el.cloneNode(true);
        c.setAttribute('aria-hidden', 'true');
        c.setAttribute('data-clone', '');
        c.removeAttribute('data-reveal');
        c.querySelectorAll('a, button, [tabindex]').forEach(function (x) { x.tabIndex = -1; });
        track.appendChild(c);
      });
      wrap.classList.add('is-auto');
      var first = track.children[items.length];
      var period = function () { return first.offsetLeft - items[0].offsetLeft; };
      var pos = track.scrollLeft, applied = pos, last = 0, hold = 0, hover = false, focus = false, seen = true;

      wrap.addEventListener('mouseenter', function () { hover = true; });
      wrap.addEventListener('mouseleave', function () { hover = false; });
      wrap.addEventListener('focusin', function () { focus = true; });
      wrap.addEventListener('focusout', function () { focus = false; });
      track.addEventListener('pointerdown', function () { hold = performance.now() + 4000; }, { passive: true });
      track.addEventListener('touchstart', function () { hold = performance.now() + 4000; }, { passive: true });
      if (window.IntersectionObserver) new IntersectionObserver(function (en) { seen = en[0].isIntersecting; }).observe(wrap);

      function tick(t) {
        var dt = last ? Math.min(64, t - last) : 0;
        last = t;
        var p = period();
        if (p > 0) {
          // Someone else moved it (arrows, drag, swipe, wheel): follow them, wait a moment, then carry on.
          if (Math.abs(track.scrollLeft - applied) > 2) {
            pos = track.scrollLeft;
            hold = t + 2500;
            if (pos < 2) pos += p - 2; // going back past the start: continue from the same spot in the copy
          } else if (!hover && !focus && seen && !down && t > hold && !document.hidden) {
            pos += SPEED * dt / 1000;
          }
          if (pos >= p) pos -= p;
          if (Math.abs(track.scrollLeft - pos) >= 1) track.scrollLeft = pos;
          applied = track.scrollLeft;
        }
        requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    }
  };
})();
