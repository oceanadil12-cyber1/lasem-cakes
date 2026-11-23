/* La Sem — header and menus, product scroller, lightbox, click-to-play videos, "open now" badge. */
(function () {
  'use strict';

  // Header turns solid once the page scrolls (inner pages keep it solid all the time).
  var header = document.querySelector('[data-header]');
  function onScroll() { header.classList.toggle('is-solid', window.scrollY > 40); }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Mobile menu.
  var toggle = document.querySelector('[data-nav-toggle]');
  var list = document.querySelector('[data-nav-list]');
  function setMenu(open) {
    toggle.setAttribute('aria-expanded', String(open));
    list.classList.toggle('is-open', open);
    document.body.style.overflow = open && window.innerWidth <= 1120 ? 'hidden' : '';
    if (open) header.classList.add('is-solid'); else onScroll();
  }
  toggle.addEventListener('click', function () { setMenu(toggle.getAttribute('aria-expanded') !== 'true'); });
  list.addEventListener('click', function (e) { if (e.target.closest('a')) setMenu(false); });

  // Dropdowns: hover opens them on desktop (CSS); the arrow buttons open them by click/tap and keyboard.
  var subToggles = document.querySelectorAll('[data-sub-toggle]');
  function closeSubs(except) {
    subToggles.forEach(function (b) {
      if (b === except) return;
      b.setAttribute('aria-expanded', 'false');
      b.parentElement.classList.remove('is-open');
      document.getElementById(b.getAttribute('aria-controls')).classList.remove('is-open');
    });
  }
  subToggles.forEach(function (b) {
    b.addEventListener('click', function () {
      var open = b.getAttribute('aria-expanded') !== 'true';
      closeSubs(b);
      b.setAttribute('aria-expanded', String(open));
      b.parentElement.classList.toggle('is-open', open);
      document.getElementById(b.getAttribute('aria-controls')).classList.toggle('is-open', open);
    });
  });
  document.addEventListener('click', function (e) { if (!e.target.closest('.has-sub')) closeSubs(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { setMenu(false); closeSubs(); closeLightbox(); }
  });

  // Arrow buttons for horizontal scrollers.
  var scroller = document.querySelector('[data-scroller]');
  document.querySelectorAll('[data-scroll]').forEach(function (b) {
    b.addEventListener('click', function () {
      scroller.scrollBy({ left: Number(b.dataset.scroll) * scroller.clientWidth * 0.8, behavior: 'smooth' });
    });
  });

  // Lightbox for galleries, product photos and award badges.
  var lb, lbImg, lbCount, group = [], index = 0, opener = null;
  function buildLightbox() {
    lb = document.createElement('div');
    lb.className = 'lightbox';
    lb.hidden = true;
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');
    lb.setAttribute('aria-label', 'Photo viewer');
    lb.innerHTML = '<img alt=""><button class="lb-btn lb-close" type="button" aria-label="Close">&times;</button>' +
      '<button class="lb-btn lb-prev" type="button" aria-label="Previous photo">&#8249;</button>' +
      '<button class="lb-btn lb-next" type="button" aria-label="Next photo">&#8250;</button><p class="lb-count"></p>';
    document.body.appendChild(lb);
    lbImg = lb.querySelector('img');
    lbCount = lb.querySelector('.lb-count');
    lb.querySelector('.lb-close').addEventListener('click', closeLightbox);
    lb.querySelector('.lb-prev').addEventListener('click', function () { show(index - 1); });
    lb.querySelector('.lb-next').addEventListener('click', function () { show(index + 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) closeLightbox(); });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) return;
      if (e.key === 'ArrowLeft') show(index - 1);
      if (e.key === 'ArrowRight') show(index + 1);
    });
    var startX = null;
    lb.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1));
      startX = null;
    });
  }
  function show(i) {
    index = (i + group.length) % group.length;
    var a = group[index];
    lbImg.classList.remove('is-in');
    void lbImg.offsetWidth;
    lbImg.src = a.href;
    lbImg.classList.add('is-in');
    lbImg.alt = (a.querySelector('img') || {}).alt || '';
    var cap = a.getAttribute('data-caption');
    lbCount.textContent = cap ? cap + (group.length > 1 ? '  ·  ' + (index + 1) + ' / ' + group.length : '') : (group.length > 1 ? (index + 1) + ' / ' + group.length : '');
    lb.querySelector('.lb-prev').hidden = lb.querySelector('.lb-next').hidden = group.length < 2;
  }
  function closeLightbox() {
    if (!lb || lb.hidden) return;
    lb.hidden = true;
    document.body.style.overflow = '';
    if (opener) opener.focus();
    document.dispatchEvent(new Event('showcase:resume'));
  }
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-lightbox]');
    if (!a || e.ctrlKey || e.metaKey) return;
    e.preventDefault();
    if (!lb) buildLightbox();
    var wrap = a.closest('[data-lightbox-group]');
    // Looping sliders repeat their items (data-clone); each photo counts once.
    group = wrap ? Array.prototype.slice.call(wrap.querySelectorAll('[data-lightbox]')).filter(function (x) { return !x.closest('[data-clone]'); }) : [a];
    opener = a;
    lb.hidden = false;
    document.body.style.overflow = 'hidden';
    document.dispatchEvent(new Event('showcase:resume'));
    show(Math.max(0, group.findIndex(function (x) { return x.href === a.href; })));
    lb.querySelector('.lb-close').focus();
  });

  // "Order Request" pop-up (any [data-order-request] button; data-request-for names the cake).
  var req = document.getElementById('order-request');
  if (req && typeof req.showModal === 'function') {
    var reqForm = req.querySelector('[data-request-form]');
    var reqDone = req.querySelector('[data-request-done]');
    var reqStatus = req.querySelector('[data-request-status]');
    var reqSend = req.querySelector('[data-request-send]');
    var reqFor = req.querySelector('[data-request-for]');
    var reqForLabel = req.querySelector('[data-request-for-label]');
    var reqOpener = null;
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-order-request]');
      if (!b) return;
      e.preventDefault();
      reqOpener = b;
      var what = b.getAttribute('data-request-for') || '';
      reqFor.value = what;
      reqForLabel.textContent = what ? 'For: ' + what : '';
      reqForLabel.hidden = !what;
      reqForm.hidden = false;
      reqDone.hidden = true;
      reqStatus.textContent = '';
      req.showModal();
      var first = reqForm.querySelector('input[name="name"]');
      if (first) first.focus();
    });
    req.addEventListener('click', function (e) {
      if (e.target === req || e.target.closest('[data-request-close]')) req.close();
    });
    req.addEventListener('close', function () { if (reqOpener) reqOpener.focus(); });
    reqForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var date = reqForm.elements.date, phone = reqForm.elements.phone;
      if (!date.value || !phone.value.trim()) {
        reqStatus.textContent = 'Please add the order date and your phone number.';
        (!date.value ? date : phone).focus();
        return;
      }
      reqSend.disabled = true;
      reqStatus.textContent = 'Sending…';
      fetch(reqForm.action, { method: 'POST', body: new FormData(reqForm), headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
        .then(function (res) {
          if (res.ok) {
            reqForm.reset();
            reqForm.hidden = true;
            reqDone.hidden = false;
            reqDone.querySelector('button.btn').focus();
          } else {
            reqStatus.textContent = res.error || 'Sorry, it could not be sent. Please call us instead.';
          }
        })
        .catch(function () { reqStatus.textContent = 'Sorry, it could not be sent. Please call us instead.'; })
        .then(function () { reqSend.disabled = false; });
    });
  }

  // YouTube: load the player only when the visitor presses play.
  document.querySelectorAll('[data-yt]').forEach(function (b) {
    b.addEventListener('click', function () {
      var f = document.createElement('iframe');
      f.src = 'https://www.youtube-nocookie.com/embed/' + b.dataset.yt + '?autoplay=1&rel=0';
      f.title = b.getAttribute('aria-label') || 'Video';
      f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
      f.allowFullscreen = true;
      var box = document.createElement('div');
      box.className = 'yt';
      box.appendChild(f);
      b.replaceWith(box);
    });
  });

  // "Open now" badge and today's row in the hours table (Mississauga time).
  try {
    var parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Toronto', weekday: 'long', hour: 'numeric', minute: 'numeric', hour12: false }).formatToParts(new Date());
    var get = function (t) { return parts.find(function (p) { return p.type === t; }).value; };
    var mins = (Number(get('hour')) % 24) * 60 + Number(get('minute'));
    var day = get('weekday');
    document.querySelectorAll('[data-open-now]').forEach(function (badge) {
      var o = (badge.dataset.opens || '08:30').split(':'), c = (badge.dataset.closes || '18:00').split(':');
      var open = mins >= o[0] * 60 + Number(o[1]) && mins < c[0] * 60 + Number(c[1]);
      badge.textContent = open ? 'Open now' : 'Closed now';
      badge.classList.add(open ? 'is-open' : 'is-closed');
    });
    var row = document.querySelector('.hours tr[data-day="' + day + '"]');
    if (row) row.classList.add('is-today');
  } catch (e) { /* older browsers: no badge */ }

  // Home page occasion explorer: pointing at an occasion shows its photo; otherwise the photos take
  // turns every few seconds (only while the section is on screen, never with reduced motion, and not
  // on phones, where the occasions are swipeable cards with their own photos).
  document.querySelectorAll('[data-occx]').forEach(function (box) {
    var rows = Array.prototype.slice.call(box.querySelectorAll('[data-occx-row]'));
    var shots = Array.prototype.slice.call(box.querySelectorAll('[data-occx-shot]'));
    var stage = box.querySelector('.occx-stage');
    var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var DELAY = 5000, cur = 0, timer = null, held = false, seen = false;
    box.style.setProperty('--occx-delay', DELAY + 'ms');
    function show(i) {
      cur = (i + rows.length) % rows.length;
      rows.forEach(function (r, n) { r.classList.toggle('is-on', n === cur); });
      shots.forEach(function (s, n) { s.classList.toggle('is-on', n === cur); });
    }
    function schedule() {
      clearTimeout(timer);
      var cycling = !held && seen && !calm && !document.hidden && getComputedStyle(stage).display !== 'none';
      box.classList.toggle('is-paused', !cycling);
      if (cycling) timer = setTimeout(function () { show(cur + 1); schedule(); }, DELAY);
    }
    rows.forEach(function (r, n) {
      var point = function () { held = true; show(n); schedule(); };
      r.addEventListener('mouseenter', point);
      r.addEventListener('focus', point);
      r.addEventListener('mouseleave', function () { held = false; schedule(); });
      r.addEventListener('blur', function () { held = false; schedule(); });
    });
    stage.addEventListener('mouseenter', function () { held = true; schedule(); });
    stage.addEventListener('mouseleave', function () { held = false; schedule(); });
    document.addEventListener('visibilitychange', schedule);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { seen = en[0].isIntersecting; schedule(); }, { threshold: 0.3 }).observe(box);
    }
  });

  // Product photo showcase: cross-fades between photos and keeps cycling on its own while in view.
  // A manual pick restarts the timer; it only stops in the lightbox, in a hidden tab, or with reduced motion.
  document.querySelectorAll('[data-showcase]').forEach(function (box) {
    var slides = Array.prototype.slice.call(box.querySelectorAll('.showcase-slide'));
    var thumbs = Array.prototype.slice.call(box.querySelectorAll('[data-showcase-go]'));
    var counter = box.querySelector('[data-showcase-index]');
    if (slides.length < 2) return;
    var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var DELAY = 5000, cur = 0, timer = null, seen = false;
    box.style.setProperty('--showcase-delay', DELAY + 'ms');
    function show(i) {
      cur = (i + slides.length) % slides.length;
      slides.forEach(function (s, n) {
        s.classList.toggle('is-active', n === cur);
        s.tabIndex = n === cur ? 0 : -1;
      });
      thumbs.forEach(function (t, n) {
        t.classList.toggle('is-active', n === cur);
        t.setAttribute('aria-selected', n === cur ? 'true' : 'false');
      });
      if (counter) counter.textContent = cur + 1;
      var t = thumbs[cur], row = t && t.parentNode;
      if (row && row.scrollWidth > row.clientWidth) row.scrollTo({ left: t.offsetLeft - (row.clientWidth - t.offsetWidth) / 2, behavior: calm ? 'auto' : 'smooth' });
    }
    function schedule() {
      clearTimeout(timer);
      var open = document.querySelector('.lightbox:not([hidden])');
      var playing = seen && !calm && !document.hidden && !open;
      box.classList.toggle('is-playing', playing);
      if (playing) timer = setTimeout(function () { show(cur + 1); schedule(); }, DELAY);
    }
    // A manual choice shows that photo and restarts the timer.
    function pick(i) { show(i); schedule(); }
    thumbs.forEach(function (t, n) { t.addEventListener('click', function () { pick(n); }); });
    box.querySelectorAll('[data-showcase-step]').forEach(function (b) {
      b.addEventListener('click', function () { pick(cur + Number(b.getAttribute('data-showcase-step'))); });
    });
    box.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { e.preventDefault(); pick(cur - 1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); pick(cur + 1); }
    });
    var stage = box.querySelector('.showcase-stage');
    // Swipe on touch screens.
    var sx = null;
    stage.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    stage.addEventListener('touchend', function (e) {
      if (sx === null) return;
      var dx = e.changedTouches[0].clientX - sx;
      sx = null;
      if (Math.abs(dx) > 40) pick(cur + (dx < 0 ? 1 : -1));
    });
    document.addEventListener('visibilitychange', schedule);
    document.addEventListener('showcase:resume', schedule);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { seen = en[0].isIntersecting; schedule(); }, { threshold: 0.1 }).observe(box);
    } else { seen = true; schedule(); }
  });

  // Awards hero: the medal flips from one winning year to the next on its own while on screen.
  document.querySelectorAll('[data-aw-cycle]').forEach(function (box) {
    var coins = Array.prototype.slice.call(box.querySelectorAll('.aw-coin'));
    var tabs = Array.prototype.slice.call(box.querySelectorAll('[data-aw-go]'));
    var title = box.querySelector('[data-aw-caption]');
    if (coins.length < 2) return;
    var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var DELAY = 3000, timer = null, seen = false;
    var cur = Math.max(0, coins.findIndex(function (c) { return c.classList.contains('is-active'); }));
    box.style.setProperty('--aw-delay', DELAY + 'ms');
    function show(i) {
      var next = (i + coins.length) % coins.length;
      if (next === cur) return;
      var old = coins[cur];
      old.classList.remove('is-active');
      old.classList.add('is-leaving');
      old.tabIndex = -1;
      setTimeout(function () { old.classList.remove('is-leaving'); }, 800);
      cur = next;
      coins[cur].classList.add('is-active');
      coins[cur].tabIndex = 0;
      tabs.forEach(function (t, n) {
        t.classList.toggle('is-active', n === cur);
        t.setAttribute('aria-selected', n === cur ? 'true' : 'false');
      });
      if (title) title.textContent = coins[cur].getAttribute('data-aw-title') || '';
    }
    function schedule() {
      clearTimeout(timer);
      var playing = seen && !calm && !document.hidden;
      box.classList.toggle('is-playing', playing);
      if (playing) timer = setTimeout(function () { show(cur + 1); schedule(); }, DELAY);
    }
    tabs.forEach(function (t, n) { t.addEventListener('click', function () { show(n); schedule(); }); });
    document.addEventListener('visibilitychange', schedule);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { seen = en[0].isIntersecting; schedule(); }, { threshold: 0.2 }).observe(box);
    } else { seen = true; schedule(); }
  });

  // Rows marked data-chip-slider get arrows, drag and wheel scrolling (js/chip-slider.js, when the page loads it).
  if (window.chipSlider) document.querySelectorAll('[data-chip-slider]').forEach(window.chipSlider);

  // Blocks marked data-reveal fade in as they scroll into view; numbers with data-count count up.
  // Without IntersectionObserver, or with reduced motion, everything simply shows.
  var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if ('IntersectionObserver' in window && !still) {
    var reveal = document.querySelectorAll('[data-reveal]');
    if (reveal.length) {
      document.documentElement.classList.add('js-reveal');
      var seen = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (!en.isIntersecting) return;
          en.target.classList.add('is-in');
          seen.unobserve(en.target);
        });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
      reveal.forEach(function (el) { seen.observe(el); });
    }
    var counters = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        counters.unobserve(en.target);
        var el = en.target, end = Number(el.dataset.count), suffix = el.dataset.suffix || '', t0 = null;
        var step = function (t) {
          if (t0 === null) t0 = t;
          var p = Math.min(1, (t - t0) / 1400);
          el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))) + suffix;
          if (p < 1) requestAnimationFrame(step);
        };
        el.textContent = '0' + suffix;
        requestAnimationFrame(step);
      });
    }, { threshold: 0.6 });
    document.querySelectorAll('[data-count]').forEach(function (el) { counters.observe(el); });
  }
})();
