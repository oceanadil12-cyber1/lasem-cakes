/* La Sem — ordering from the table (dine-in/index.php).
 * Welcome → menu → your order → priority number with live status. On a phone the guest's orders
 * are remembered for the day so they can follow them; on a table tablet the page starts fresh for
 * the next guest. Prices shown are a preview: api/dine-in.php prices every order from the menu.
 * Also drives the order-tracking page and the "Now serving" board.
 */
(function () {
  'use strict';

  var money = new Intl.NumberFormat('en-CA', { style: 'currency', currency: 'CAD' });
  function fmt(c) { return money.format(c / 100); }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function post(api, data) {
    return fetch(api, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(data) })
      .then(function (r) { return r.json(); });
  }
  function store(kind) { try { return window[kind]; } catch (e) { return null; } }
  function load(kind, key, fallback) { try { var v = store(kind).getItem(key); return v ? JSON.parse(v) : fallback; } catch (e) { return fallback; } }
  function save(kind, key, value) { try { store(kind).setItem(key, JSON.stringify(value)); } catch (e) { /* private mode */ } }
  function today() { return new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Toronto' }).format(new Date()); }

  var STEPS = [['received', 'Received'], ['preparing', 'Preparing'], ['ready', 'Ready'], ['done', 'Enjoy']];
  function stepsHtml(o) {
    var at = STEPS.findIndex(function (s) { return s[0] === o.step; });
    return '<ol class="di-steps">' + STEPS.map(function (s, i) {
      return '<li class="' + (i < at ? 'is-done' : i === at ? 'is-now' : '') + '"><i></i><span>' + s[1] + '</span></li>';
    }).join('') + '</ol>';
  }
  function statusCard(o, extra) {
    return '<article class="di-status is-' + esc(o.step) + '">' +
      '<p class="di-status-label">Your number</p>' +
      '<p class="di-number">' + o.queue + '</p>' +
      (o.table ? '<p class="di-status-table">' + esc(o.table) + (o.name ? ' · ' + esc(o.name) : '') + '</p>' : '') +
      (o.step === 'cancelled' ? '' : stepsHtml(o)) +
      '<h2 class="di-status-head">' + esc(o.label) + '</h2><p class="di-status-text">' + esc(o.text) + '</p>' +
      (extra || '') +
      '<details class="di-receipt"><summary>What you ordered · ' + fmt(o.total) + '</summary><ul>' +
        o.lines.map(function (l) { return '<li><span>' + l.qty + '× ' + esc(l.name) + (l.detail ? ' <em>' + esc(l.detail) + '</em>' : '') + '</span><b>' + fmt(l.total) + '</b></li>'; }).join('') +
        '<li class="di-receipt-sum"><span>HST</span><b>' + fmt(o.tax) + '</b></li><li class="di-receipt-sum"><span>Total</span><b>' + fmt(o.total) + '</b></li></ul></details>' +
    '</article>';
  }

  // A soft chime and a buzz when an order turns ready (works once the guest has tapped the page).
  var audio = null;
  document.addEventListener('pointerdown', function () {
    try { if (!audio) audio = new (window.AudioContext || window.webkitAudioContext)(); if (audio.state === 'suspended') audio.resume(); } catch (e) { /* no audio */ }
  }, { passive: true });
  function ding() {
    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
    if (!audio) return;
    try {
      [880, 1320].forEach(function (f, i) {
        var o = audio.createOscillator(), g = audio.createGain(), t = audio.currentTime + i * 0.18;
        o.frequency.value = f; g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.3, t + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + 0.8);
        o.connect(g); g.connect(audio.destination); o.start(t); o.stop(t + 0.85);
      });
    } catch (e) { /* no audio */ }
  }

  /* ================= "Now serving" board ================= */

  var board = document.querySelector('[data-board]');
  if (board) {
    var lastReady = null;
    var clock = board.querySelector('[data-clock]');
    var timeFmt = new Intl.DateTimeFormat('en-US', { timeZone: 'America/Toronto', hour: 'numeric', minute: '2-digit' });
    var nums = function (list) { return list.length ? list.map(function (n) { return '<b>' + n + '</b>'; }).join('') : '<span class="di-board-none">–</span>'; };
    var refresh = function () {
      fetch(board.dataset.api + '?action=board', { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
        if (!d.ok) return;
        board.querySelector('[data-board-preparing]').innerHTML = nums(d.preparing);
        board.querySelector('[data-board-ready]').innerHTML = nums(d.ready);
        if (lastReady && d.ready.some(function (n) { return lastReady.indexOf(n) === -1; })) ding();
        lastReady = d.ready;
      }).catch(function () { /* try again next time */ });
    };
    var soundBtn = board.querySelector('[data-board-sound]');
    soundBtn.addEventListener('click', function () { soundBtn.hidden = true; ding(); });
    setInterval(function () { clock.textContent = timeFmt.format(new Date()); }, 1000);
    clock.textContent = timeFmt.format(new Date());
    refresh();
    setInterval(refresh, 4000);
    return;
  }

  var app = document.querySelector('[data-app]');
  if (!app) return;
  var main = app.querySelector('.di-main');

  /* ================= follow one order ================= */

  if (app.dataset.mode === 'track') {
    var api = app.dataset.api, ref = { n: app.dataset.n, k: app.dataset.k }, prevStep = null;
    var poll = function () {
      post(api, { action: 'track', orders: [ref] }).then(function (d) {
        var o = d.ok && d.orders && d.orders[0];
        if (!o) { main.innerHTML = '<div class="di-center"><h1>Order not found</h1><p class="di-muted">Please ask our team at the counter.</p></div>'; return; }
        if (prevStep && prevStep !== o.step && o.step === 'ready') ding();
        prevStep = o.step;
        document.title = (o.step === 'ready' ? 'Ready! ' : '') + 'Order ' + o.queue + ' · La Sem Cakes';
        main.innerHTML = statusCard(o, '<p class="di-muted di-small">This page updates by itself. Keep it open.</p>');
        if (o.step !== 'done' && o.step !== 'cancelled') setTimeout(poll, 5000);
      }).catch(function () { setTimeout(poll, 8000); });
    };
    poll();
    return;
  }

  /* ================= a table ================= */

  var cfg = JSON.parse(document.getElementById('di-data').textContent);
  var sheet = app.querySelector('[data-sheet]');
  var cartbar = app.querySelector('[data-cartbar]');
  var toastEl = app.querySelector('[data-toast]');
  var CART_KEY = 'lasem-dinein-cart-' + cfg.table.code;
  var MINE_KEY = 'lasem-dinein-orders';

  var variants = {}, products = {};
  cfg.menu.categories.forEach(function (cat) {
    cat.products.forEach(function (p) {
      products[p.id] = { p: p, cat: cat };
      p.variants.forEach(function (v) { variants[v.id] = { p: p, v: v, cat: cat }; });
    });
  });

  var state = {
    screen: 'welcome',
    name: cfg.tablet ? '' : (load('localStorage', 'lasem-dinein-name', '') || ''),
    cart: cfg.tablet ? [] : load('sessionStorage', CART_KEY, []).filter(function (l) { return variants[l.variant] && !variants[l.variant].p.soldOut; }),
    notes: '',
    key: newKey(),
    sending: false,
    placed: null,        // the order just placed: {order, track}
    mine: [],            // this phone's orders today, with live status
    resetTimer: null
  };
  function newKey() { return Date.now().toString(36) + Math.random().toString(36).slice(2, 10); }
  function saveCart() { if (!cfg.tablet) save('sessionStorage', CART_KEY, state.cart); }

  function totals() {
    var sub = 0, taxable = 0, count = 0;
    state.cart.forEach(function (l) {
      var x = variants[l.variant], line = x.v.price * l.qty;
      sub += line; count += l.qty;
      if (cfg.taxAll || x.cat.taxable) taxable += line;
    });
    var tax = Math.round(taxable * cfg.taxRate);
    return { sub: sub, tax: tax, total: sub + tax, count: count };
  }

  function toast(msg, bad) {
    toastEl.textContent = msg;
    toastEl.classList.toggle('is-bad', !!bad);
    toastEl.hidden = false;
    clearTimeout(toast.t);
    toast.t = setTimeout(function () { toastEl.hidden = true; }, bad ? 6000 : 2600);
  }

  function go(screen) {
    state.screen = screen;
    render();
    window.scrollTo(0, 0);
  }

  /* ---------- screens ---------- */

  function render() {
    var t = totals();
    var html = '';
    if (state.screen === 'welcome') html = welcomeHtml();
    else if (state.screen === 'menu') html = menuHtml();
    else if (state.screen === 'cart') html = cartHtml(t);
    else if (state.screen === 'placed') html = placedHtml();
    main.innerHTML = html;
    main.dataset.screen = state.screen;
    var showBar = state.screen === 'menu' && t.count > 0;
    cartbar.hidden = !showBar;
    if (showBar) cartbar.innerHTML = '<button class="di-btn di-btn-solid di-btn-block" type="button" data-go="cart"><span class="di-cartcount">' + t.count + '</span>View your order<b>' + fmt(t.total) + '</b></button>';
    if (state.screen === 'menu') {
      wireSearch();
      if (window.chipSlider) window.chipSlider(main.querySelector('.di-cats'));
    }
  }

  function welcomeHtml() {
    var mine = state.mine.filter(function (o) { return o.step !== 'done' && o.step !== 'cancelled'; });
    return '<section class="di-welcome">' +
      '<p class="di-kicker">Welcome to La Sem</p>' +
      '<h1>Order from <span>' + esc(cfg.table.name) + '</span></h1>' +
      (cfg.open
        ? '<p class="di-lead">Choose from our cakes, pastries and drinks. We\'ll prepare your order and give you a number to follow.</p>' +
          '<form class="di-namebox" data-start><label for="di-name">Your first name <i>(optional, so we can call it out)</i></label>' +
          '<input id="di-name" name="name" maxlength="30" autocomplete="given-name" value="' + esc(state.name) + '" placeholder="e.g. Maria">' +
          '<button class="di-btn di-btn-solid di-btn-lg di-btn-block" type="submit">Start my order</button></form>'
        : '<p class="di-closed">' + esc(cfg.closed) + '</p>') +
      (mine.length ? '<div class="di-mine"><h2>Your orders</h2>' + mine.map(function (o) {
        return '<a class="di-mine-row is-' + esc(o.step) + '" href="#" data-show="' + esc(o.number) + '"><b>' + o.queue + '</b><span>' + esc(o.label) + '</span><i>View</i></a>';
      }).join('') + '</div>' : '') +
      '<p class="di-muted di-small">' + esc(cfg.payNote) + '</p>' +
    '</section>';
  }

  function cardHtml(p) {
    var sold = !!p.soldOut;
    var min = Math.min.apply(null, p.variants.map(function (v) { return v.price; }));
    return '<button class="di-card' + (sold ? ' is-sold' : '') + '" type="button" data-open="' + esc(p.id) + '" data-search="' + esc((p.name + ' ' + (p.desc || '')).toLowerCase()) + '"' + (sold ? ' disabled' : '') + '>' +
      (p.img ? '<img src="' + esc(p.img) + '" alt="" loading="lazy" width="400" height="300">' : '<span class="di-noimg" aria-hidden="true"></span>') +
      '<span class="di-card-body"><b>' + esc(p.name) + '</b><span>' + (sold ? 'Sold out' : (p.variants.length > 1 ? 'From ' : '') + fmt(min)) + '</span></span>' +
      (sold ? '' : '<i class="di-add" aria-hidden="true">+</i>') +
    '</button>';
  }

  function menuHtml() {
    var cats = cfg.menu.categories;
    return '<div class="di-menutop"><input type="search" placeholder="Search the menu" data-search aria-label="Search the menu">' +
      '<nav class="di-cats">' + cats.map(function (c) { return '<a href="#di-cat-' + esc(c.id) + '">' + esc(c.name) + '</a>'; }).join('') + '</nav></div>' +
      cats.map(function (c) {
        return '<section class="di-cat" id="di-cat-' + esc(c.id) + '" data-cat><h2>' + esc(c.name) + '</h2><div class="di-grid">' + c.products.map(cardHtml).join('') + '</div></section>';
      }).join('') + '<p class="di-muted di-center" data-noresult hidden>Nothing matches your search.</p>';
  }

  function wireSearch() {
    var input = main.querySelector('[data-search]');
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase(), any = false;
      main.querySelectorAll('[data-cat]').forEach(function (sec) {
        var shown = 0;
        sec.querySelectorAll('.di-card').forEach(function (c) { var hit = !q || c.dataset.search.indexOf(q) !== -1; c.hidden = !hit; if (hit) shown++; });
        sec.hidden = !shown;
        if (shown) any = true;
      });
      main.querySelector('[data-noresult]').hidden = any;
    });
  }

  function lineLabel(l) {
    var x = variants[l.variant];
    return { name: x.p.name, sub: [x.v.label, l.choice].filter(Boolean).join(' · ') };
  }

  function cartHtml(t) {
    if (!state.cart.length) {
      return '<section class="di-cart"><button class="di-back" type="button" data-go="menu">&larr; Menu</button><h1>Your order</h1><p class="di-muted">Nothing here yet.</p>' +
        '<button class="di-btn di-btn-solid di-btn-block" type="button" data-go="menu">See the menu</button></section>';
    }
    return '<section class="di-cart"><button class="di-back" type="button" data-go="menu">&larr; Add more</button>' +
      '<h1>Your order</h1><p class="di-muted">' + esc(cfg.table.name) + (state.name ? ' · ' + esc(state.name) : '') + '</p>' +
      '<ul class="di-lines">' + state.cart.map(function (l, i) {
        var n = lineLabel(l), x = variants[l.variant];
        return '<li><div><b>' + esc(n.name) + '</b>' + (n.sub ? '<span>' + esc(n.sub) + '</span>' : '') + (l.note ? '<em>“' + esc(l.note) + '”</em>' : '') + '</div>' +
          '<div class="di-line-side"><b>' + fmt(x.v.price * l.qty) + '</b><span class="di-qty"><button type="button" data-line="' + i + '" data-d="-1" aria-label="One less">&minus;</button><output>' + l.qty + '</output><button type="button" data-line="' + i + '" data-d="1" aria-label="One more">+</button></span></div></li>';
      }).join('') + '</ul>' +
      '<label class="di-notes"><span>Anything we should know? <i>Optional</i></span><textarea rows="2" maxlength="300" data-notes placeholder="Allergies, a birthday candle, extra plates…">' + esc(state.notes) + '</textarea></label>' +
      '<dl class="di-sum"><div><dt>Subtotal</dt><dd>' + fmt(t.sub) + '</dd></div><div><dt>HST</dt><dd>' + fmt(t.tax) + '</dd></div><div class="di-sum-total"><dt>Total</dt><dd>' + fmt(t.total) + '</dd></div></dl>' +
      '<p class="di-paynote">' + esc(cfg.payNote) + '</p>' +
      '<p class="di-err" data-err hidden></p>' +
      '<button class="di-btn di-btn-solid di-btn-lg di-btn-block" type="button" data-place' + (state.sending ? ' disabled' : '') + '>' + (state.sending ? 'Sending…' : 'Place order · ' + fmt(t.total)) + '</button>' +
    '</section>';
  }

  function placedHtml() {
    var p = state.placed;
    if (!p) return '';
    var o = p.order;
    var extra;
    if (cfg.tablet) {
      extra = '<div class="di-takeaway"><img src="' + esc(cfg.qr) + '?n=' + encodeURIComponent(p.track.n) + '&k=' + encodeURIComponent(p.track.k) + '" alt="QR code to follow this order on your phone" width="180" height="180">' +
        '<p><b>Follow your order on your phone</b>Scan this code with your camera.</p></div>' +
        '<button class="di-btn di-btn-solid di-btn-lg di-btn-block" type="button" data-done>Done <span data-countdown></span></button>';
    } else {
      extra = '<p class="di-muted di-small">Keep this page open: it updates by itself, and your phone buzzes when your order is ready.</p>' +
        '<button class="di-btn di-btn-line di-btn-block" type="button" data-more>Order something else</button>';
    }
    return statusCard(o, extra);
  }

  /* ---------- item pop-up ---------- */

  function openItem(id) {
    var x = products[id];
    if (!x || x.p.soldOut) return;
    var p = x.p;
    var sel = { variant: p.variants[0].id, choice: null, qty: 1, note: '' };
    function price() { return variants[sel.variant].v.price * sel.qty; }
    function draw() {
      sheet.innerHTML = '<div class="di-sheet-back" data-close></div><div class="di-sheet-panel">' +
        '<button class="di-x" type="button" data-close aria-label="Close">&times;</button>' +
        (p.img ? '<img class="di-sheet-img" src="' + esc(p.img) + '" alt="">' : '') +
        '<div class="di-sheet-body"><h2 id="di-sheet-title">' + esc(p.name) + '</h2>' + (p.desc ? '<p class="di-muted">' + esc(p.desc) + '</p>' : '') +
        (p.variants.length > 1 ? '<fieldset><legend>Size</legend>' + p.variants.map(function (v) {
          return '<label class="di-opt"><input type="radio" name="variant" value="' + esc(v.id) + '"' + (v.id === sel.variant ? ' checked' : '') + '><span>' + esc(v.label) + '</span><b>' + fmt(v.price) + '</b></label>';
        }).join('') + '</fieldset>' : '') +
        (p.choice ? '<fieldset><legend>' + esc(p.choice.label) + ' <i>Required</i></legend>' + p.choice.options.map(function (o) {
          return '<label class="di-opt"><input type="radio" name="choice" value="' + esc(o) + '"' + (o === sel.choice ? ' checked' : '') + '><span>' + esc(o) + '</span></label>';
        }).join('') + '</fieldset>' : '') +
        '<label class="di-notes"><span>Special request <i>Optional</i></span><input maxlength="150" data-note value="' + esc(sel.note) + '" placeholder="e.g. warmed up, no powdered sugar"></label>' +
        '<p class="di-err" data-item-err hidden></p></div>' +
        '<div class="di-sheet-foot"><span class="di-qty di-qty-lg"><button type="button" data-q="-1" aria-label="One less"' + (sel.qty < 2 ? ' disabled' : '') + '>&minus;</button><output>' + sel.qty + '</output><button type="button" data-q="1" aria-label="One more">+</button></span>' +
        '<button class="di-btn di-btn-solid di-btn-lg" type="button" data-add>Add · ' + fmt(price()) + '</button></div></div>';
    }
    draw();
    sheet.hidden = false;
    document.body.classList.add('di-locked');
    sheet.onclick = function (e) {
      if (e.target.closest('[data-close]')) { closeSheet(); return; }
      var q = e.target.closest('[data-q]');
      if (q) { sel.note = sheet.querySelector('[data-note]').value; sel.qty = Math.max(1, Math.min(30, sel.qty + Number(q.dataset.q))); draw(); return; }
      if (e.target.closest('[data-add]')) {
        sel.note = sheet.querySelector('[data-note]').value.trim();
        if (p.choice && !sel.choice) { var err = sheet.querySelector('[data-item-err]'); err.textContent = 'Please choose a ' + p.choice.label.toLowerCase() + '.'; err.hidden = false; return; }
        var same = state.cart.find(function (l) { return l.variant === sel.variant && (l.choice || null) === (sel.choice || null) && (l.note || '') === sel.note; });
        if (same) same.qty = Math.min(30, same.qty + sel.qty);
        else state.cart.push({ variant: sel.variant, qty: sel.qty, choice: sel.choice, note: sel.note });
        saveCart();
        closeSheet();
        render();
        toast(p.name + ' added');
      }
    };
    sheet.onchange = function (e) {
      if (e.target.name === 'variant') sel.variant = e.target.value;
      if (e.target.name === 'choice') sel.choice = e.target.value;
      sheet.querySelector('[data-add]').textContent = 'Add · ' + fmt(price());
    };
  }
  function closeSheet() { sheet.hidden = true; sheet.innerHTML = ''; document.body.classList.remove('di-locked'); }

  /* ---------- placing the order ---------- */

  function place() {
    if (state.sending || !state.cart.length) return;
    state.sending = true;
    render();
    post(cfg.api, {
      action: 'order', code: cfg.table.code, name: state.name, notes: state.notes, key: state.key, kiosk: cfg.tablet,
      lines: state.cart.map(function (l) { return { variant: l.variant, qty: l.qty, choice: l.choice, note: l.note }; })
    }).then(function (d) {
      state.sending = false;
      if (!d.ok) { render(); var err = main.querySelector('[data-err]'); if (err) { err.textContent = d.error; err.hidden = false; } return; }
      state.placed = d;
      state.cart = [];
      state.notes = '';
      state.key = newKey();
      saveCart();
      if (!cfg.tablet) {
        var mine = load('localStorage', MINE_KEY, []).filter(function (m) { return m.day === today(); });
        mine.push({ n: d.track.n, k: d.track.k, day: today() });
        save('localStorage', MINE_KEY, mine.slice(-10));
        state.mine.push(d.order);
      }
      go('placed');
      if (cfg.tablet) startReset();
      else pollMine();
    }).catch(function () {
      state.sending = false;
      render();
      var err = main.querySelector('[data-err]');
      if (err) { err.textContent = 'No connection. Your order wasn\'t sent. Please try again.'; err.hidden = false; }
    });
  }

  // Tablet: back to the welcome screen for the next guest.
  function startReset() {
    var left = 60;
    clearInterval(state.resetTimer);
    var show = function () { var el = main.querySelector('[data-countdown]'); if (el) el.textContent = '(' + left + ')'; };
    show();
    state.resetTimer = setInterval(function () {
      left--;
      show();
      if (left <= 0) resetTablet();
    }, 1000);
  }
  function resetTablet() {
    clearInterval(state.resetTimer);
    state.placed = null; state.name = ''; state.cart = []; state.notes = '';
    closeSheet();
    go('welcome');
  }

  // Phone: follow today's orders from this phone.
  function pollMine() {
    clearTimeout(pollMine.t);
    var refs = load('localStorage', MINE_KEY, []).filter(function (m) { return m.day === today(); });
    if (!refs.length) return;
    post(cfg.api, { action: 'track', orders: refs.map(function (m) { return { n: m.n, k: m.k }; }) }).then(function (d) {
      if (!d.ok) return;
      d.orders.forEach(function (o) {
        var before = state.mine.find(function (m) { return m.number === o.number; });
        if (before && before.step !== o.step && o.step === 'ready') { ding(); toast('Order ' + o.queue + ' is ready!'); }
      });
      state.mine = d.orders;
      if (state.placed) {
        var cur = d.orders.find(function (o) { return o.number === state.placed.order.number; });
        if (cur) state.placed.order = cur;
      }
      if (state.screen === 'placed' || state.screen === 'welcome') render();
      if (d.orders.some(function (o) { return o.step !== 'done' && o.step !== 'cancelled'; })) pollMine.t = setTimeout(pollMine, 5000);
    }).catch(function () { pollMine.t = setTimeout(pollMine, 8000); });
  }

  /* ---------- clicks ---------- */

  app.addEventListener('submit', function (e) {
    if (!e.target.matches('[data-start]')) return;
    e.preventDefault();
    state.name = e.target.querySelector('#di-name').value.trim().slice(0, 30);
    if (!cfg.tablet) save('localStorage', 'lasem-dinein-name', state.name);
    go('menu');
  });
  app.addEventListener('input', function (e) { if (e.target.matches('[data-notes]')) state.notes = e.target.value; });
  app.addEventListener('click', function (e) {
    var t = e.target;
    var el;
    if ((el = t.closest('[data-open]'))) { openItem(el.dataset.open); return; }
    if ((el = t.closest('[data-go]'))) { go(el.dataset.go); return; }
    if ((el = t.closest('[data-line]'))) {
      var l = state.cart[Number(el.dataset.line)];
      l.qty += Number(el.dataset.d);
      if (l.qty < 1) state.cart.splice(Number(el.dataset.line), 1);
      saveCart();
      render();
      return;
    }
    if (t.closest('[data-place]')) { place(); return; }
    if (t.closest('[data-done]')) { resetTablet(); return; }
    if (t.closest('[data-more]')) { state.placed = null; go('menu'); return; }
    if ((el = t.closest('[data-show]'))) {
      e.preventDefault();
      var o = state.mine.find(function (m) { return m.number === el.dataset.show; });
      if (o) { state.placed = { order: o, track: null }; go('placed'); }
      return;
    }
    if ((el = t.closest('.di-cats a'))) {
      e.preventDefault();
      var target = document.querySelector(el.getAttribute('href'));
      if (target) window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - main.querySelector('.di-menutop').offsetHeight - 70, behavior: 'smooth' });
      return;
    }
    if (t.closest('[data-call]')) callStaff(t.closest('[data-call]'));
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !sheet.hidden) closeSheet(); });

  function callStaff(btn) {
    if (btn.disabled) return;
    btn.disabled = true;
    post(cfg.api, { action: 'call', code: cfg.table.code, name: state.name }).then(function (d) {
      toast(d.ok ? 'We\'ve let our team know. Someone will be with you shortly.' : d.error, !d.ok);
    }).catch(function () { toast('No connection. Please wave to our team.', true); })
      .then(function () { setTimeout(function () { btn.disabled = false; }, 30000); });
  }

  // Tablet: if a guest walks away mid-order, start fresh after 3 quiet minutes.
  if (cfg.tablet) {
    var idle = null;
    var bump = function () {
      clearTimeout(idle);
      idle = setTimeout(function () { if (state.screen !== 'welcome' && state.screen !== 'placed') resetTablet(); }, 180000);
    };
    ['pointerdown', 'keydown', 'scroll'].forEach(function (ev) { document.addEventListener(ev, bump, { passive: true }); });
    bump();
  }

  render();
  if (!cfg.tablet) pollMine();
})();
