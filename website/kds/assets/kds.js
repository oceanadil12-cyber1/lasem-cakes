/* La Sem kitchen display: asks kds/api.php for orders every 5 seconds, shows them as cards in
   time order (pickup orders by pickup time, table orders by when they were placed), chimes for
   new orders and staff calls, and moves orders along with one tap.
   Times are always Mississauga time, whatever the tablet's own clock zone is. */
(function () {
  'use strict';

  var body = document.body;
  var API = body.dataset.api;
  if (!API) return;
  var CSRF = body.dataset.csrf;
  var grid = document.getElementById('kds-grid');
  var toastEl = document.getElementById('kds-toast');
  var conn = document.getElementById('kds-conn');
  var soundBtn = document.getElementById('kds-sound');

  var state = {
    orders: [], today: '', tomorrow: '', ordering: true,
    calls: [], callsSeen: null, dinein: { serve: 'table', lateMins: 15 },
    view: 'today',
    prep: Number(body.dataset.prep) || 30,
    sound: body.dataset.sound === '1',
    seen: null,          // order numbers already shown (null until the first load, so nothing chimes on open)
    fresh: {},           // numbers that arrived since the screen opened: highlighted for a while
    skew: 0,             // server clock minus this tablet's clock
    lastJson: '',
    busy: false
  };
  try { var v = localStorage.getItem('lasem-kds-view'); if (v) state.view = v; } catch (e) { /* storage blocked */ }

  var TZ = 'America/Toronto';
  var timeFmt = new Intl.DateTimeFormat('en-US', { timeZone: TZ, hour: 'numeric', minute: '2-digit', hour12: true });
  var clockFmt = new Intl.DateTimeFormat('en-US', { timeZone: TZ, weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
  var money = new Intl.NumberFormat('en-CA', { style: 'currency', currency: 'CAD' });

  function now() { return Date.now() + state.skew; }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function hm(ms) { return timeFmt.format(new Date(ms)).replace(/\s?([ap])\.?m\.?/i, function (m, p) { return ' ' + p.toUpperCase() + 'M'; }); }
  function mins(ms) { return Math.round(Math.abs(ms) / 60000); }
  function span(ms) {
    var m = mins(ms);
    if (m < 60) return m + ' min';
    var h = Math.floor(m / 60), r = m % 60;
    return h + ' h' + (r ? ' ' + r + ' min' : '');
  }

  /* ---------- talking to the server ---------- */

  function api(data) {
    data.csrf = CSRF;
    return fetch(API, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(data)
    }).then(function (r) {
      if (r.status === 401 || r.status === 403) { location.reload(); throw new Error('signed out'); }
      return r.json();
    });
  }

  function load() {
    return api({ action: 'orders' }).then(function (d) {
      if (!d.ok) throw new Error(d.error || 'error');
      conn.hidden = true;
      apply(d);
    }).catch(function () { conn.hidden = false; });
  }

  function apply(d) {
    if (d.now) state.skew = d.now - Date.now();
    if (d.today) { state.today = d.today; state.tomorrow = d.tomorrow; }
    if ('ordering' in d) state.ordering = d.ordering;
    if (d.dinein) state.dinein = d.dinein;
    if (d.calls) applyCalls(d.calls);
    var orders = d.orders || [];
    // New orders: chime once and highlight them.
    if (state.seen) {
      var arrived = orders.filter(function (o) { return !state.seen[o.number] && o.status === 'new'; });
      arrived.forEach(function (o) { state.fresh[o.number] = now(); });
      if (arrived.length) {
        chime();
        toast(arrived.length === 1 ? (arrived[0].type === 'dine-in' ? 'New table order #' + arrived[0].queue + ', ' + arrived[0].table : 'New order: ' + arrived[0].name + (arrived[0].delivery ? ', delivery ' : ', pickup ') + pickupWhen(arrived[0])) : arrived.length + ' new orders');
      }
    }
    state.seen = {};
    orders.forEach(function (o) { state.seen[o.number] = true; });
    state.orders = orders;
    var json = JSON.stringify(orders);
    if (json !== state.lastJson) { state.lastJson = json; render(); }
  }

  /* ---------- what each order needs ---------- */

  // queue | start | late | preparing | ready | done
  function stage(o) {
    var t = now();
    if (o.status === 'picked_up') return 'done';
    if (o.status === 'ready') return 'ready';
    if (o.type === 'dine-in') {   // made now: start straight away; late after the set minutes
      if (t - o.placed > state.dinein.lateMins * 60000) return 'late';
      return o.status === 'preparing' ? 'preparing' : 'start';
    }
    if (o.status === 'preparing') return t > o.pickup ? 'late' : 'preparing';
    if (t > o.pickup) return 'late';
    if (t >= o.pickup - state.prep * 60000) return 'start';
    return 'queue';
  }

  function inView(o, view) {
    var open = o.status !== 'picked_up';
    if (view === 'today') return open && o.day <= state.today;
    if (view === 'tomorrow') return open && o.day === state.tomorrow;
    if (view === 'tables') return open && o.type === 'dine-in';
    if (view === 'ready') return o.status === 'ready';
    if (view === 'done') return o.status === 'picked_up';
    return false;
  }

  function pickupWhen(o) {
    return (o.day === state.tomorrow ? 'tomorrow ' : o.day < state.today ? 'on ' + new Date(o.pickup).toLocaleDateString('en-CA', { timeZone: TZ, month: 'short', day: 'numeric' }) + ' ' : '') + hm(o.pickup);
  }

  function servedWord() { return state.dinein.serve === 'counter' ? 'Collected' : 'Served'; }

  function relText(o) {
    var t = now(), diff = o.pickup - t;
    if (o.type === 'dine-in') {
      if (o.status === 'picked_up') return servedWord() + ' ' + hm(o.pickedAt || t);
      return o.table + ' · ordered ' + (t - o.placed < 60000 ? 'just now' : span(t - o.placed) + ' ago');
    }
    if (o.status === 'picked_up') return 'Picked up ' + hm(o.pickedAt || t);
    if (o.day === state.tomorrow) return 'Tomorrow';
    if (o.day < state.today) return span(diff) + ' late';
    if (diff >= 0) return 'in ' + span(diff);
    return o.status === 'ready' ? 'due ' + span(diff) + ' ago' : span(diff) + ' late';
  }

  var LABELS = { queue: 'Later today', start: 'Start now', late: 'Late', preparing: 'Preparing', ready: 'Ready', done: 'Picked up' };

  /* ---------- drawing ---------- */

  function card(o) {
    var st = stage(o);
    var isNew = o.status === 'new';
    var dine = o.type === 'dine-in';
    var fresh = state.fresh[o.number] && now() - state.fresh[o.number] < 10 * 60000;
    var count = o.items.reduce(function (n, i) { return n + i.qty; }, 0);
    var items = o.items.map(function (i) {
      return '<li><b class="q">' + i.qty + '×</b><span><b>' + esc(i.name) + '</b>' +
        (i.detail ? '<em>' + esc(i.detail) + '</em>' : '') +
        (i.note ? '<span class="inote">✎ ' + esc(i.note) + '</span>' : '') + '</span></li>';
    }).join('');

    var btn = function (to, label, cls) {
      return '<button type="button" class="kds-btn ' + (cls || '') + '" data-move="' + to + '" data-number="' + esc(o.number) + '">' + label + '</button>';
    };
    var acts = '';
    if (o.status === 'new' || o.status === 'confirmed') acts = btn('ready', 'Ready', 'kds-btn-small') + btn('preparing', 'Start', 'kds-btn-go');
    else if (o.status === 'preparing') acts = btn('ready', 'Ready', 'kds-btn-ready');
    else if (o.status === 'ready') acts = btn('back', 'Back', 'kds-btn-small') + btn('picked_up', 'Done', 'kds-btn-go');
    else acts = btn('back', 'Undo', 'kds-btn-small');

    // The card shows only what the kitchen needs: when the order came in, what kind of order it is,
    // who it's for, and the items. Phone numbers, addresses, payment and notes stay in the admin.
    var kind = dine ? 'dinein' : (o.delivery ? 'delivery' : 'pickup');
    var type = dine ? 'Dine-in · ' + esc(o.table) : (o.delivery ? 'Delivery' : 'Pickup');
    var label = st === 'queue' && o.day === state.tomorrow ? 'Tomorrow' : (st === 'done' ? (dine ? servedWord() : (o.delivery ? 'Delivered' : 'Picked up')) : LABELS[st]);
    return '<article class="kds-card is-' + st + (dine ? ' is-table' : '') + (fresh ? ' is-fresh' : '') + '" data-number="' + esc(o.number) + '">' +
      '<header class="kds-card-head">' +
        '<div class="kds-when"><b>' + hm(o.placed) + '</b><span>Received' + (o.day !== state.today ? ' · for ' + (o.day === state.tomorrow ? 'tomorrow' : new Date(o.pickup).toLocaleDateString('en-CA', { timeZone: TZ, month: 'short', day: 'numeric' })) : '') + '</span></div>' +
        '<span class="kds-type is-' + kind + '">' + type + '</span>' +
      '</header>' +
      '<div class="kds-who"><b>' + esc(o.name || (dine ? o.table : '')) + '</b>' +
        '<span>Order <b>' + esc(dine ? '#' + o.queue : o.number) + '</b> · ' + count + ' item' + (count === 1 ? '' : 's') + (isNew ? ' <i class="kds-new">New</i>' : '') + '</span></div>' +
      '<ul class="kds-items">' + items + '</ul>' +
      '<footer class="kds-card-foot"><span class="kds-stage">' + label + '</span><div class="kds-acts">' + acts + '</div></footer>' +
    '</article>';
  }

  function render() {
    var t = now();
    var shown = state.orders.filter(function (o) { return inView(o, state.view); });
    if (state.view === 'done') shown.sort(function (a, b) { return b.pickedAt - a.pickedAt; });

    var empty = {
      today: ['No pickups left today', 'New orders appear here by themselves, with a chime.'],
      tomorrow: ['Nothing for tomorrow yet', 'Orders for tomorrow will show here.'],
      tables: ['No table orders right now', 'Orders from the table QR codes and tablets appear here.'],
      ready: ['Nothing waiting for pickup', 'Orders you mark ready wait here until the customer collects.'],
      done: ['Nothing picked up yet today', '']
    }[state.view];
    grid.innerHTML = callsHtml() + (state.ordering ? '' : '<p class="kds-banner">Online ordering is switched off in the admin. No new orders will come in until it\'s turned back on.</p>') +
      (shown.length ? shown.map(card).join('')
        : '<div class="kds-empty"><b>' + empty[0] + '</b><span>' + empty[1] + '</span></div>');

    // Counts on the tabs and the summary (the summary is about today).
    var c = { today: 0, tomorrow: 0, tables: 0, ready: 0, done: 0 };
    var s = { start: 0, preparing: 0, ready: 0, late: 0 };
    state.orders.forEach(function (o) {
      ['today', 'tomorrow', 'tables', 'ready', 'done'].forEach(function (v) { if (inView(o, v)) c[v]++; });
      if (!inView(o, 'today')) return;
      var st = stage(o);
      if (st === 'start' || (st === 'late' && o.status !== 'preparing')) s.start++;
      if (o.status === 'preparing') s.preparing++;
      if (o.status === 'ready') s.ready++;
      if (st === 'late') s.late++;
    });
    Object.keys(c).forEach(function (k) { document.getElementById('count-' + k).textContent = c[k]; });
    Object.keys(s).forEach(function (k) {
      document.getElementById('stat-' + k).textContent = s[k];
      document.querySelector('[data-stat="' + k + '"]').classList.toggle('has', s[k] > 0);
    });
    document.querySelectorAll('.kds-views [data-view]').forEach(function (b) { b.classList.toggle('on', b.dataset.view === state.view); });
    document.title = (s.start + s.late ? '(' + (s.start + s.late) + ') ' : '') + 'Kitchen display · La Sem';
    state.rendered = t;
  }

  // Every second: the clock and the "in 12 min" / timer texts. A full redraw every 20 s moves
  // orders into "Start now" and "Late" as the time comes.
  function tick() {
    var t = now();
    document.getElementById('kds-clock').textContent = clockFmt.format(new Date(t));
    if (t - (state.rendered || 0) > 20000) { render(); return; }
    document.querySelectorAll('[data-rel]').forEach(function (el) {
      var o = state.orders.find(function (x) { return x.number === el.dataset.rel; });
      if (o) el.textContent = relText(o);
    });
    document.querySelectorAll('[data-since]').forEach(function (el) {
      var since = Number(el.dataset.since);
      el.textContent = 'Started ' + hm(since) + ' · ' + span(t - since);
    });
  }

  /* ---------- actions ---------- */

  /* ---------- "call staff" from a table ---------- */

  function applyCalls(calls) {
    if (state.callsSeen) {
      var fresh = calls.filter(function (c) { return !state.callsSeen[c.id]; });
      if (fresh.length) { chime(); toast(fresh[0].table + ' is calling for staff'); }
    }
    state.callsSeen = {};
    calls.forEach(function (c) { state.callsSeen[c.id] = true; });
    var changed = JSON.stringify(calls) !== JSON.stringify(state.calls);
    state.calls = calls;
    if (changed) state.lastJson = '';
  }
  function callsHtml() {
    if (!state.calls.length) return '';
    return '<div class="kds-calls">' + state.calls.map(function (c) {
      return '<div class="kds-call"><b>' + esc(c.table) + '</b><span>is calling for staff' + (c.name ? ' (' + esc(c.name) + ')' : '') + ' · ' + hm(c.at) + '</span>' +
        '<button type="button" class="kds-btn kds-btn-go" data-call-done="' + esc(c.id) + '">On my way</button></div>';
    }).join('') + '</div>';
  }

  grid.addEventListener('click', function (e) {
    var cd = e.target.closest('[data-call-done]');
    if (cd) {
      cd.disabled = true;
      api({ action: 'call-done', id: cd.dataset.callDone }).then(function (d) {
        if (d.calls) { applyCalls(d.calls); render(); }
      }).catch(function () { cd.disabled = false; toast('No connection. Please try again.', true); });
      return;
    }
    var b = e.target.closest('[data-move]');
    if (!b || state.busy) return;
    state.busy = true;
    b.disabled = true;
    b.classList.add('is-busy');
    var num = b.dataset.number, to = b.dataset.move;
    api({ action: 'move', number: num, to: to }).then(function (d) {
      if (!d.ok) toast(d.error || 'That didn\'t work. Please try again.', true);
      else {
        var o = state.orders.find(function (x) { return x.number === num; });
        var name = o ? o.name : num;
        if (o && o.type === 'dine-in') name = '#' + o.queue + ' (' + o.table + ')';
        toast({ preparing: 'Started: ' + name, ready: 'Ready: ' + name, picked_up: (o && o.type === 'dine-in' ? servedWord() : 'Picked up') + ': ' + name, back: 'Moved back: ' + name }[to]);
        delete state.fresh[num];
      }
      if (d.orders) { state.lastJson = ''; apply(d); }
    }).catch(function () {
      toast('No connection. The order wasn\'t changed.', true);
    }).then(function () {
      state.busy = false;
      b.disabled = false;
      b.classList.remove('is-busy');
    });
  });

  function setView(v) {
    state.view = v;
    try { localStorage.setItem('lasem-kds-view', v); } catch (e) { /* storage blocked */ }
    render();
    window.scrollTo(0, 0);
  }
  document.querySelectorAll('[data-view]').forEach(function (b) {
    b.addEventListener('click', function () { setView(b.dataset.view); });
  });

  /* ---------- chime ---------- */

  var audio = null;
  function unlockAudio() {
    try {
      if (!audio) audio = new (window.AudioContext || window.webkitAudioContext)();
      if (audio.state === 'suspended') audio.resume();
    } catch (e) { /* no audio on this device */ }
  }
  document.addEventListener('pointerdown', unlockAudio, { passive: true });

  function chime() {
    if (!state.sound) return;
    try {
      unlockAudio();
      if (!audio) return;
      [784, 988, 1175].forEach(function (f, i) {   // G5, B5, D6: a bright bakery-bell chord
        var o = audio.createOscillator(), g = audio.createGain(), t0 = audio.currentTime + i * 0.16;
        o.type = 'sine';
        o.frequency.value = f;
        g.gain.setValueAtTime(0.0001, t0);
        g.gain.exponentialRampToValueAtTime(0.35, t0 + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.9);
        o.connect(g); g.connect(audio.destination);
        o.start(t0); o.stop(t0 + 0.95);
      });
    } catch (e) { /* no audio */ }
  }

  function showSound() {
    soundBtn.setAttribute('aria-pressed', state.sound ? 'true' : 'false');
    soundBtn.title = state.sound ? 'Sound on' : 'Sound off';
    soundBtn.classList.toggle('is-off', !state.sound);
  }
  soundBtn.addEventListener('click', function () {
    state.sound = !state.sound;
    showSound();
    if (state.sound) { unlockAudio(); chime(); }
    saveSettings();
  });

  /* ---------- toast ---------- */

  var toastTimer = null;
  function toast(msg, bad) {
    if (!msg) return;
    toastEl.textContent = msg;
    toastEl.classList.toggle('is-bad', !!bad);
    toastEl.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.hidden = true; }, bad ? 6000 : 3500);
  }

  /* ---------- settings ---------- */

  var sheet = document.getElementById('kds-settings');
  var form = document.getElementById('kds-settings-form');
  document.getElementById('kds-settings-open').addEventListener('click', function () {
    form.prep_minutes.value = state.prep;
    form.sound.checked = state.sound;
    sheet.hidden = false;
  });
  sheet.addEventListener('click', function (e) { if (e.target.closest('[data-close]')) sheet.hidden = true; });
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    state.prep = Math.max(5, Math.min(1440, Number(form.prep_minutes.value) || 30));
    state.sound = form.sound.checked;
    showSound();
    saveSettings().then(function () { sheet.hidden = true; render(); });
  });
  function saveSettings() {
    return api({ action: 'settings', prep_minutes: state.prep, sound: state.sound }).then(function (d) {
      if (d.ok) toast('Settings saved');
      else toast(d.error, true);
    }).catch(function () { toast('Settings couldn\'t be saved.', true); });
  }

  /* ---------- full screen, keep the screen on ---------- */

  document.getElementById('kds-full').addEventListener('click', function () {
    if (document.fullscreenElement) document.exitFullscreen();
    else if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen().catch(function () {});
  });
  var lock = null;
  function keepAwake() {
    if (!('wakeLock' in navigator) || document.hidden) return;
    navigator.wakeLock.request('screen').then(function (l) { lock = l; }).catch(function () {});
  }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { keepAwake(); load(); } });

  /* ---------- start ---------- */

  showSound();
  keepAwake();
  load().then(function () { render(); });
  setInterval(function () { if (!document.hidden) load(); }, 5000);
  setInterval(tick, 1000);
  tick();
})();
