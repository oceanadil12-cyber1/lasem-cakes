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
    document.body.style.overflow = open && window.innerWidth <= 900 ? 'hidden' : '';
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
    lbImg.src = a.href;
    lbImg.alt = (a.querySelector('img') || {}).alt || '';
    lbCount.textContent = group.length > 1 ? (index + 1) + ' / ' + group.length : '';
    lb.querySelector('.lb-prev').hidden = lb.querySelector('.lb-next').hidden = group.length < 2;
  }
  function closeLightbox() {
    if (!lb || lb.hidden) return;
    lb.hidden = true;
    document.body.style.overflow = '';
    if (opener) opener.focus();
  }
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-lightbox]');
    if (!a || e.ctrlKey || e.metaKey) return;
    e.preventDefault();
    if (!lb) buildLightbox();
    var wrap = a.closest('[data-lightbox-group]');
    group = wrap ? Array.prototype.slice.call(wrap.querySelectorAll('[data-lightbox]')) : [a];
    opener = a;
    lb.hidden = false;
    document.body.style.overflow = 'hidden';
    show(group.indexOf(a));
    lb.querySelector('.lb-close').focus();
  });

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
})();
