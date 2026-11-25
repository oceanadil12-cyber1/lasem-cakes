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

  // "Upload pictures" on the enquiry forms: up to 5 images, 10 MB in total; lists the chosen files.
  var PHOTO_MAX = 5, PHOTO_MB = 10;
  function photoProblem(input) {
    var files = input.files || [], total = 0;
    if (files.length > PHOTO_MAX) return 'Please choose up to ' + PHOTO_MAX + ' photos.';
    for (var i = 0; i < files.length; i++) {
      if (!/^image\//.test(files[i].type) && !/\.(heic|heif)$/i.test(files[i].name)) return '“' + files[i].name + '” is not a photo.';
      total += files[i].size;
    }
    if (total > PHOTO_MB * 1048576) return 'These photos come to ' + (total / 1048576).toFixed(1) + ' MB. Please keep them under ' + PHOTO_MB + ' MB in total.';
    return '';
  }
  function showPhotos(input) {
    var list = input.parentNode.querySelector('[data-photo-list]');
    if (!list) return;
    var files = input.files || [], problem = photoProblem(input), names = [];
    for (var i = 0; i < files.length; i++) names.push(files[i].name);
    list.textContent = problem || (files.length ? files.length + (files.length === 1 ? ' photo: ' : ' photos: ') + names.join(', ') : 'Up to ' + PHOTO_MAX + ' photos, ' + PHOTO_MB + ' MB in total');
    list.classList.toggle('is-bad', !!problem);
    list.classList.toggle('is-set', !problem && files.length > 0);
  }
  document.addEventListener('change', function (e) {
    if (e.target.matches && e.target.matches('[data-photos]')) showPhotos(e.target);
  });
  document.addEventListener('submit', function (e) {
    var input = e.target.querySelector && e.target.querySelector('[data-photos]');
    if (!input || e.target.hasAttribute('data-request-form')) return;
    var problem = photoProblem(input);
    if (problem) { e.preventDefault(); showPhotos(input); input.focus(); }
  });

  // "Order Request" pop-up (any [data-order-request] button; data-request-for names the cake).
  var req = document.getElementById('order-request');
  if (req && typeof req.showModal === 'function') {
    var reqForm = req.querySelector('[data-request-form]');
    var reqDone = req.querySelector('[data-request-done]');
    var reqStatus = req.querySelector('[data-request-status]');
    var reqSend = req.querySelector('[data-request-send]');
    var reqFor = req.querySelector('[data-request-for]');
    var reqOpener = null;
    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-order-request]');
      if (!b) return;
      e.preventDefault();
      reqOpener = b;
      var what = b.getAttribute('data-request-for') || '';
      // Pick the matching occasion; a cake or bakery item not in the list gets its own option.
      var extra = reqFor.querySelector('option[data-extra]');
      if (extra) extra.remove();
      var match = Array.prototype.find.call(reqFor.options, function (o) { return o.value && o.value.toLowerCase() === what.toLowerCase(); });
      if (what && !match) {
        match = new Option(what, what);
        match.setAttribute('data-extra', '');
        reqFor.add(match, 1);
      }
      reqFor.value = match ? match.value : '';
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
      var photos = reqForm.querySelector('[data-photos]');
      if (photos && photoProblem(photos)) {
        reqStatus.textContent = photoProblem(photos);
        showPhotos(photos);
        return;
      }
      reqSend.disabled = true;
      reqStatus.textContent = 'Sending…';
      fetch(reqForm.action, { method: 'POST', body: new FormData(reqForm), headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
        .then(function (res) {
          if (res.ok) {
            reqForm.reset();
            if (photos) showPhotos(photos);
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
          el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))).toLocaleString('en-US') + suffix;
          if (p < 1) requestAnimationFrame(step);
        };
        el.textContent = '0' + suffix;
        requestAnimationFrame(step);
      });
    }, { threshold: 0.6 });
    document.querySelectorAll('[data-count]').forEach(function (el) { counters.observe(el); });
  }

  // Slider photos past the first are loading="lazy" so they don't compete with the page.
  // These fetch them early enough that a slide is never blank when it moves in.
  // Articles & videos: preview frames load as each group of clips scrolls near.
  var posterGroups = document.querySelectorAll('.post-media, .vrow');
  function setPosters(g) { g.querySelectorAll('video[data-poster]').forEach(function (v) { v.poster = v.getAttribute('data-poster'); v.removeAttribute('data-poster'); }); }
  if ('IntersectionObserver' in window) {
    var posterWatch = new IntersectionObserver(function (en) {
      en.forEach(function (x) { if (x.isIntersecting) { setPosters(x.target); posterWatch.unobserve(x.target); } });
    }, { rootMargin: '600px 0px' });
    posterGroups.forEach(function (g) { posterWatch.observe(g); });
  } else posterGroups.forEach(setPosters);

  // Articles & videos: only one clip plays at a time.
  document.addEventListener('play', function (e) {
    if (e.target.tagName !== 'VIDEO') return;
    document.querySelectorAll('video').forEach(function (v) { if (v !== e.target && !v.paused) v.pause(); });
  }, true);

  // Slider frames must never scroll sideways (focusing an arrow can make browsers do that).
  document.querySelectorAll('.team-viewport, .banner-media').forEach(function (frame) {
    frame.addEventListener('scroll', function () { if (frame.scrollLeft) frame.scrollLeft = 0; });
  });

  function fetchLazy(scope) {
    scope.querySelectorAll('img[loading="lazy"]').forEach(function (im) { im.loading = 'eager'; });
  }

  // About-us banner: photos slide left every 3 seconds, looping back to the first.
  document.querySelectorAll('[data-banner-slider]').forEach(function (banner) {
    var track = banner.querySelector('[data-banner-track]');
    if (document.readyState === 'complete') fetchLazy(track);
    else window.addEventListener('load', function () { fetchLazy(track); });
    var count = track.children.length;
    if (count < 2) return;
    track.appendChild(track.children[0].cloneNode(true));
    var i = 0;
    function go() {
      i++;
      track.classList.remove('no-anim');
      track.style.transform = 'translateX(' + (-100 * i) + '%)';
    }
    track.addEventListener('transitionend', function () {
      if (i < count) return;
      i = 0;
      track.classList.add('no-anim');
      track.style.transform = 'translateX(0)';
    });
    function tick() {
      if (document.hidden) return;
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches && i >= count - 1) {
        i = -1; // no transition to wait for: jump straight back to the first photo
      }
      go();
    }
    var timer;
    function restart() {
      // Always begin on Fold 1 and give it a full 3 seconds.
      clearInterval(timer);
      i = 0;
      track.classList.add('no-anim');
      track.style.transform = 'translateX(0)';
      timer = setInterval(tick, 3000);
    }
    restart();
    // Back/forward navigation can restore a frozen page mid-slide; start over from Fold 1.
    window.addEventListener('pageshow', function (e) { if (e.persisted) restart(); });
  });

  // About-us team photos: 1 → 2 → 3 → 1 every 3 seconds (always forward), plus arrows, dots and swipe.
  document.querySelectorAll('[data-team-slider]').forEach(function (box) {
    var track = box.querySelector('[data-team-track]');
    if ('IntersectionObserver' in window) {
      var near = new IntersectionObserver(function (en) {
        if (!en[0].isIntersecting) return;
        fetchLazy(track); near.disconnect();
      }, { rootMargin: '400px 0px' });
      near.observe(box);
    } else fetchLazy(track);
    var dots = box.querySelectorAll('[data-team-dots] button');
    var count = track.children.length, i = 0, timer = null, onScreen = false;
    if (count < 2) return;
    // A copy of photo 1 after photo 3 lets the loop carry on forward instead of rewinding past photo 2.
    var copy = track.children[0].cloneNode(true);
    copy.setAttribute('aria-hidden', 'true');
    track.appendChild(copy);
    function place(n, animate) {
      track.style.transition = animate ? '' : 'none';
      track.style.transform = 'translateX(' + (-100 * n) + '%)';
      if (!animate) void track.offsetWidth; // apply the jump before any later animated move
    }
    function show(n) {
      if (n < 0) { place(count, false); n = count - 1; } // from photo 1 back to photo 3: step in from the copy
      i = n;
      place(i, true);
      var on = i % count;
      dots.forEach(function (d, k) { if (k === on) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current'); });
    }
    track.addEventListener('transitionend', function () { if (i >= count) { i = 0; place(0, false); } });
    function next() {
      if (i >= count) { i = 0; place(0, false); } // finish a loop that had no transition (reduced motion)
      show(i + 1);
    }
    // The 3-second timer runs only while the slider is on screen; any arrow, dot or swipe restarts it.
    function start() {
      clearInterval(timer);
      if (onScreen) timer = setInterval(function () { if (!document.hidden) next(); }, 3000);
    }
    box.querySelector('[data-team-prev]').addEventListener('click', function () { show((i % count) - 1); start(); });
    box.querySelector('[data-team-next]').addEventListener('click', function () { next(); start(); });
    dots.forEach(function (d, k) { d.addEventListener('click', function () { if (i >= count) place(0, false); show(k); start(); }); });
    window.addEventListener('pageshow', function (e) { if (e.persisted) { i = 0; place(0, false); show(0); start(); } });
    // Swipe on touch screens.
    var x0 = null;
    box.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    box.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 40) { if (dx < 0) next(); else show((i % count) - 1); start(); }
    });
    // Each time the slider comes into view it begins again at photo 1, so visitors see 1 → 2 → 3.
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) {
        onScreen = en[0].isIntersecting;
        if (onScreen) { i = 0; place(0, false); show(0); }
        start();
      }, { threshold: 0.35 }).observe(box);
    } else { onScreen = true; start(); }
  });
})();
