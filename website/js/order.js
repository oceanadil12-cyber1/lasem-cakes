/* La Sem — online ordering for pickup.
 * Menu (from the page), item pop-up with sizes and flavours, cart kept in the browser,
 * pickup date and time, checkout. The server re-prices everything (api/order.php),
 * so the totals shown here are a preview.
 */
(function () {
  'use strict';

  var cfg = window.LASEM_ORDER;
  var menu = JSON.parse(document.getElementById('menu-data').textContent);
  var menuEl = document.querySelector('[data-menu]');
  var panel = document.querySelector('[data-cart-panel]');
  var box = document.querySelector('[data-cart]');
  var itemSheet = document.querySelector('[data-item-sheet]');
  var cartSheet = document.querySelector('[data-cart-sheet]');
  var fab = document.querySelector('[data-cart-fab]');
  var CART_KEY = 'lasem-cart-v1';
  var money = new Intl.NumberFormat('en-CA', { style: 'currency', currency: menu.currency || 'CAD' });

  // Lookups: variant id → {product, variant, category}; product id → {product, category}.
  var variants = {}, products = {};
  menu.categories.forEach(function (cat) {
    cat.products.forEach(function (p) {
      products[p.id] = { p: p, cat: cat };
      p.variants.forEach(function (v) { variants[v.id] = { p: p, v: v, cat: cat }; });
    });
  });

  var state = {
    cart: loadCart(),
    view: 'cart',          // cart | checkout | done
    form: { date: '', time: '', name: '', phone: '', email: '', notes: '', agree: false },
    error: '',
    sending: false,
    done: null,
    key: newKey()
  };

  /* ---------- Helpers ---------- */

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function fmt(cents) { return money.format(cents / 100); }
  function newKey() { return Date.now().toString(36) + Math.random().toString(36).slice(2, 10); }

  function loadCart() {
    try {
      var raw = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
      return raw.filter(function (l) { return variants[l.variant] && l.qty > 0; });
    } catch (e) { return []; }
  }
  function saveCart() {
    try { localStorage.setItem(CART_KEY, JSON.stringify(state.cart)); } catch (e) { /* private mode: cart lasts for this visit */ }
  }

  function totals() {
    var sub = 0, taxable = 0, count = 0;
    state.cart.forEach(function (l) {
      var x = variants[l.variant];
      var line = x.v.price * l.qty;
      sub += line;
      count += l.qty;
      if (x.cat.taxable) taxable += line;
    });
    var tax = Math.round(taxable * (menu.taxRate || 0));
    return { sub: sub, tax: tax, total: sub + tax, count: count };
  }

  function fromPrice(p) {
    var min = Math.min.apply(null, p.variants.map(function (v) { return v.price; }));
    return (p.variants.length > 1 ? 'From ' : '') + fmt(min);
  }

  function isQuick(p) { return p.variants.length === 1 && !p.choice; }

  /* ---------- Menu ---------- */

  function card(p) {
    var sizes = p.variants.length > 1 ? '<span class="p-sizes">' + p.variants.map(function (v) { return esc(v.label); }).join(' · ') + '</span>' : '';
    var img = p.img
      ? '<img src="' + esc(p.img) + '" alt="" loading="lazy" width="400" height="300">'
      : '<span class="p-noimg" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 2h8l-1 4h-6zM7 7h10l-1.2 14.2a1 1 0 0 1-1 .8H9.2a1 1 0 0 1-1-.8z"/></svg></span>';
    return '<article class="p-card" data-product="' + esc(p.id) + '" data-search="' + esc((p.name + ' ' + p.desc).toLowerCase()) + '">' +
      '<button class="p-open" type="button" data-open="' + esc(p.id) + '" aria-label="' + esc(p.name + ', ' + fromPrice(p)) + '">' +
        '<span class="p-media">' + img + '</span>' +
        '<span class="p-body"><span class="p-name">' + esc(p.name) + '</span>' +
        (p.desc ? '<span class="p-desc">' + esc(p.desc) + '</span>' : '') +
        '<span class="p-foot"><span class="p-price">' + fromPrice(p) + '</span>' + sizes + '</span></span>' +
      '</button>' +
      '<button class="p-add" type="button" data-' + (isQuick(p) ? 'quick' : 'open') + '="' + esc(p.id) + '" aria-label="Add ' + esc(p.name) + '">' +
        '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6z"/></svg></button>' +
    '</article>';
  }

  function renderMenu() {
    var popular = [];
    menu.categories.forEach(function (c) { c.products.forEach(function (p) { if (p.popular) popular.push(p); }); });
    var html = '<section class="m-cat" id="cat-popular" data-cat><h2>Most ordered</h2><p class="m-cat-note">Our customers’ favourites</p><div class="p-grid">' + popular.map(card).join('') + '</div></section>';
    menu.categories.forEach(function (c) {
      html += '<section class="m-cat" id="cat-' + esc(c.id) + '" data-cat><h2>' + esc(c.name) + '</h2>' +
        (c.id === 'cakes' ? '<p class="m-cat-note">Ready-made cakes for pickup. For a custom design, <a href="' + esc(cfg.contact) + '">send us an enquiry</a>.</p>' : '') +
        '<div class="p-grid">' + c.products.map(card).join('') + '</div></section>';
    });
    html += '<p class="m-empty" data-empty hidden>No items match your search.</p>';
    menuEl.innerHTML = html;
  }

  menuEl.addEventListener('click', function (e) {
    var q = e.target.closest('[data-quick]');
    if (q) { addToCart(products[q.dataset.quick].p.variants[0].id, 1, null, ''); return; }
    var o = e.target.closest('[data-open]');
    if (o) openItem(o.dataset.open, o);
  });

  // Search.
  var search = document.querySelector('[data-menu-search]');
  search.addEventListener('input', function () {
    var q = search.value.trim().toLowerCase();
    var any = false;
    menuEl.querySelectorAll('[data-cat]').forEach(function (sec) {
      var shown = 0;
      sec.querySelectorAll('.p-card').forEach(function (c) {
        var hit = !q || c.dataset.search.indexOf(q) !== -1;
        c.hidden = !hit;
        if (hit) shown++;
      });
      // While searching, skip "Most ordered" so items don't show twice.
      sec.hidden = !shown || (q && sec.id === 'cat-popular');
      if (!sec.hidden) any = true;
    });
    menuEl.querySelector('[data-empty]').hidden = any;
  });

  // Category bar: highlight the section in view; keep the active chip visible.
  var catNav = document.querySelector('[data-cat-nav]');
  function setActive(id) {
    catNav.querySelectorAll('a').forEach(function (a) {
      var on = a.getAttribute('href') === '#' + id;
      a.classList.toggle('is-active', on);
      if (on) catNav.scrollTo({ left: a.offsetLeft - catNav.clientWidth / 2 + a.clientWidth / 2, behavior: 'smooth' });
    });
  }
  catNav.addEventListener('click', function (e) {
    var a = e.target.closest('a');
    if (!a) return;
    e.preventDefault();
    var target = document.querySelector(a.getAttribute('href'));
    if (!target) return;
    var offset = document.querySelector('[data-header]').offsetHeight + document.querySelector('[data-order-bar]').offsetHeight + 8;
    window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - offset, behavior: 'smooth' });
  });
  window.addEventListener('scroll', function () {
    var offset = document.querySelector('[data-header]').offsetHeight + document.querySelector('[data-order-bar]').offsetHeight + 40;
    var current = null;
    menuEl.querySelectorAll('[data-cat]:not([hidden])').forEach(function (sec) {
      if (sec.getBoundingClientRect().top - offset <= 0) current = sec.id;
    });
    if (current && current !== setActive.last) { setActive.last = current; setActive(current); }
  }, { passive: true });

  /* ---------- Item pop-up ---------- */

  var lastFocus = null;
  function openSheet(sheet) {
    lastFocus = document.activeElement;
    sheet.hidden = false;
    document.body.classList.add('sheet-open');
    requestAnimationFrame(function () { sheet.classList.add('is-in'); });
    var f = sheet.querySelector('[data-autofocus]') || sheet.querySelector('button, input, select, textarea');
    if (f) f.focus({ preventScroll: true });
  }
  function closeSheet(sheet) {
    if (sheet.hidden) return;
    sheet.classList.remove('is-in');
    sheet.hidden = true;
    if (itemSheet.hidden && cartSheet.hidden) document.body.classList.remove('sheet-open');
    if (sheet === cartSheet) panel.appendChild(box);
    if (lastFocus && document.contains(lastFocus)) lastFocus.focus({ preventScroll: true });
  }

  function openItem(id, opener) {
    var x = products[id];
    if (!x) return;
    var p = x.p;
    var sel = { variant: p.variants[0].id, choice: null, qty: 1, note: '' };
    var cake = x.cat.id === 'cakes';

    function price() { return variants[sel.variant].v.price * sel.qty; }
    function render() {
      itemSheet.innerHTML =
        '<div class="sheet-backdrop" data-close></div>' +
        '<div class="sheet-panel item-panel">' +
          '<button class="sheet-x" type="button" data-close aria-label="Close">&times;</button>' +
          (p.img ? '<img class="item-img" src="' + esc(p.img) + '" alt="' + esc(p.name) + '">' : '') +
          '<div class="item-body">' +
            '<h2 id="sheet-title">' + esc(p.name) + '</h2>' +
            (p.desc ? '<p class="item-desc">' + esc(p.desc) + '</p>' : '') +
            (p.variants.length > 1 ? fieldset('Size', 'Required', p.variants.map(function (v) {
              return radio('variant', v.id, v.label, fmt(v.price), sel.variant === v.id);
            })) : '<p class="item-price">' + fmt(p.variants[0].price) + '</p>') +
            (p.choice ? fieldset(p.choice.label, 'Required', p.choice.options.map(function (o) {
              return radio('choice', o, o, '', sel.choice === o);
            })) : '') +
            '<label class="item-note"><span>' + (cake ? 'Writing on the cake or special requests' : 'Special instructions') + ' <i>Optional</i></span>' +
              '<textarea rows="2" maxlength="200" data-note placeholder="' + (cake ? 'e.g. Happy Birthday Maria' : 'Anything we should know?') + '">' + esc(sel.note) + '</textarea></label>' +
            '<p class="item-err" data-item-err hidden></p>' +
          '</div>' +
          '<div class="item-actions">' +
            '<div class="qty" role="group" aria-label="Quantity">' +
              '<button type="button" data-qty="-1" aria-label="One less"' + (sel.qty <= 1 ? ' disabled' : '') + '>&minus;</button>' +
              '<output aria-live="polite">' + sel.qty + '</output>' +
              '<button type="button" data-qty="1" aria-label="One more">+</button>' +
            '</div>' +
            '<button class="btn btn-solid btn-lg item-add" type="button" data-add>Add to order · ' + fmt(price()) + '</button>' +
          '</div>' +
        '</div>';
    }
    function fieldset(legend, hint, inner) {
      return '<fieldset class="opt"><legend>' + esc(legend) + ' <i>' + esc(hint) + '</i></legend>' + inner.join('') + '</fieldset>';
    }
    function radio(name, value, label, extra, checked) {
      return '<label class="opt-row"><input type="radio" name="' + name + '" value="' + esc(value) + '"' + (checked ? ' checked' : '') + '>' +
        '<span>' + esc(label) + '</span>' + (extra ? '<b>' + esc(extra) + '</b>' : '') + '</label>';
    }

    render();
    itemSheet.onclick = function (e) {
      if (e.target.closest('[data-close]')) { closeSheet(itemSheet); return; }
      var q = e.target.closest('[data-qty]');
      if (q) {
        sel.qty = Math.max(1, Math.min(50, sel.qty + Number(q.dataset.qty)));
        sel.note = itemSheet.querySelector('[data-note]').value;
        render();
        itemSheet.querySelector('[data-qty="' + q.dataset.qty + '"]').focus();
        return;
      }
      if (e.target.closest('[data-add]')) {
        sel.note = itemSheet.querySelector('[data-note]').value.trim();
        if (p.choice && !sel.choice) {
          var err = itemSheet.querySelector('[data-item-err]');
          err.textContent = 'Please choose a ' + p.choice.label.toLowerCase() + '.';
          err.hidden = false;
          return;
        }
        addToCart(sel.variant, sel.qty, sel.choice, sel.note);
        closeSheet(itemSheet);
      }
    };
    itemSheet.onchange = function (e) {
      if (e.target.name === 'variant') sel.variant = e.target.value;
      if (e.target.name === 'choice') sel.choice = e.target.value;
      itemSheet.querySelector('[data-add]').textContent = 'Add to order · ' + fmt(price());
      var err = itemSheet.querySelector('[data-item-err]');
      if (err) err.hidden = true;
    };
    lastFocus = opener;
    openSheet(itemSheet);
  }

  /* ---------- Cart ---------- */

  function addToCart(variant, qty, choice, note) {
    var same = state.cart.find(function (l) { return l.variant === variant && (l.choice || null) === (choice || null) && (l.note || '') === (note || ''); });
    if (same) same.qty = Math.min(50, same.qty + qty);
    else state.cart.push({ variant: variant, qty: qty, choice: choice || null, note: note || '' });
    if (state.view === 'done') { state.view = 'cart'; state.done = null; }
    saveCart();
    renderCart();
    toast(variants[variant].p.name + ' added to your order');
    fab.classList.remove('bump'); void fab.offsetWidth; fab.classList.add('bump');
  }

  function lineName(l) {
    var x = variants[l.variant];
    var bits = [];
    if (x.v.label) bits.push(x.v.label);
    if (l.choice) bits.push(l.choice);
    return { name: x.p.name, sub: bits.join(' · '), note: l.note };
  }

  function summaryRows(t) {
    return '<dl class="sum">' +
      '<div><dt>Subtotal</dt><dd>' + fmt(t.sub) + '</dd></div>' +
      '<div><dt>HST</dt><dd>' + fmt(t.tax) + '</dd></div>' +
      '<div class="sum-total"><dt>Total</dt><dd>' + fmt(t.total) + '</dd></div>' +
    '</dl>';
  }

  function renderCart() {
    var t = totals();
    var html;
    if (state.view === 'done' && state.done) {
      html = renderDone();
    } else if (!state.cart.length) {
      state.view = 'cart';
      html = '<div class="cart-head"><h2>Your order</h2><button class="sheet-x cart-x" type="button" data-close-cart aria-label="Close">&times;</button></div>' +
        '<div class="cart-empty"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M10 18h28l-3 22H13z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M17 18c0-5 3-9 7-9s7 4 7 9" fill="none" stroke="currentColor" stroke-width="2"/></svg>' +
        '<p>Your order is empty.</p><p class="muted">Add cakes, pastries and more from the menu.</p></div>';
    } else if (state.view === 'checkout') {
      html = renderCheckout(t);
    } else {
      html = '<div class="cart-head"><h2>Your order</h2><button class="sheet-x cart-x" type="button" data-close-cart aria-label="Close">&times;</button></div>' +
        '<p class="cart-pickup"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7zm0 4.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/></svg>Pickup · 1275 Eglinton Ave. East</p>' +
        '<ul class="lines">' + state.cart.map(function (l, i) {
          var n = lineName(l), x = variants[l.variant];
          return '<li class="line">' +
            '<div class="line-main"><b>' + esc(n.name) + '</b>' +
              (n.sub ? '<span>' + esc(n.sub) + '</span>' : '') +
              (n.note ? '<span class="line-note">“' + esc(n.note) + '”</span>' : '') + '</div>' +
            '<div class="line-side"><b>' + fmt(x.v.price * l.qty) + '</b>' +
              '<div class="qty qty-sm" role="group" aria-label="Quantity of ' + esc(n.name) + '">' +
                '<button type="button" data-line="' + i + '" data-d="-1" aria-label="' + (l.qty === 1 ? 'Remove ' + esc(n.name) : 'One less') + '">' + (l.qty === 1 ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6l1 2h4v2H4V5h4zm-3 6h12l-1 12H7z"/></svg>' : '&minus;') + '</button>' +
                '<output>' + l.qty + '</output>' +
                '<button type="button" data-line="' + i + '" data-d="1" aria-label="One more">+</button>' +
              '</div></div>' +
          '</li>';
        }).join('') + '</ul>' +
        summaryRows(t) +
        '<button class="btn btn-solid btn-lg btn-block" type="button" data-go="checkout">Checkout · ' + fmt(t.total) + '</button>' +
        '<p class="muted small center">Pickup only. You pay at the counter.</p>';
    }
    box.innerHTML = html;
    if (state.view === 'checkout') wireCheckout();

    fab.hidden = !t.count || state.view === 'done';
    document.querySelector('[data-fab-count]').textContent = t.count;
    document.querySelector('[data-fab-total]').textContent = fmt(t.total);
  }

  box.addEventListener('click', function (e) {
    var b = e.target.closest('[data-line]');
    if (b) {
      var i = Number(b.dataset.line), l = state.cart[i];
      l.qty += Number(b.dataset.d);
      if (l.qty <= 0) state.cart.splice(i, 1);
      if (l.qty > 50) l.qty = 50;
      saveCart();
      renderCart();
      return;
    }
    var go = e.target.closest('[data-go]');
    if (go) {
      state.view = go.dataset.go;
      state.error = '';
      renderCart();
      var h = box.querySelector('h2');
      if (h) { h.setAttribute('tabindex', '-1'); h.focus({ preventScroll: true }); }
      box.scrollTop = 0;
      if (!cartSheet.hidden) cartSheet.querySelector('.sheet-panel').scrollTop = 0;
      return;
    }
    if (e.target.closest('[data-close-cart]')) closeSheet(cartSheet);
    if (e.target.closest('[data-new-order]')) {
      state.view = 'cart'; state.done = null; renderCart(); closeSheet(cartSheet);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });

  // Phones: the cart opens as a sheet from the bottom.
  fab.addEventListener('click', function () {
    cartSheet.innerHTML = '<div class="sheet-backdrop" data-close-cart-bg></div><div class="sheet-panel cart-panel"></div>';
    cartSheet.querySelector('.sheet-panel').appendChild(box);
    openSheet(cartSheet);
  });
  cartSheet.addEventListener('click', function (e) { if (e.target.closest('[data-close-cart-bg]')) closeSheet(cartSheet); });
  window.addEventListener('resize', function () { if (window.innerWidth > 1080) closeSheet(cartSheet); });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (!itemSheet.hidden) closeSheet(itemSheet);
    else if (!cartSheet.hidden) closeSheet(cartSheet);
  });

  /* ---------- Pickup times (Mississauga time) ---------- */

  function torontoNow() {
    var parts = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Toronto', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }).formatToParts(new Date());
    var g = function (t) { return Number(parts.find(function (p) { return p.type === t; }).value); };
    return { y: g('year'), m: g('month'), d: g('day'), mins: (g('hour') % 24) * 60 + g('minute') };
  }
  function hm(s) { var a = s.split(':'); return Number(a[0]) * 60 + Number(a[1]); }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function label12(m) { var h = Math.floor(m / 60), mm = m % 60; return ((h + 11) % 12 + 1) + ':' + pad(mm) + ' ' + (h < 12 ? 'am' : 'pm'); }

  function pickupDays() {
    var now = torontoNow();
    var open = hm(menu.hours.opens), close = hm(menu.hours.closes);
    var days = [];
    for (var i = 0; i <= menu.daysAhead; i++) {
      var dt = new Date(Date.UTC(now.y, now.m - 1, now.d + i));
      var start = open;
      if (i === 0) start = Math.max(open, Math.ceil((now.mins + menu.leadMinutes) / 15) * 15);
      var slots = [];
      for (var m = start; m <= close; m += 15) slots.push(m);
      if (!slots.length) continue;
      var iso = dt.getUTCFullYear() + '-' + pad(dt.getUTCMonth() + 1) + '-' + pad(dt.getUTCDate());
      var md = dt.toLocaleDateString('en-CA', { month: 'short', day: 'numeric', timeZone: 'UTC' });
      var wd = dt.toLocaleDateString('en-CA', { weekday: 'short', timeZone: 'UTC' });
      days.push({ iso: iso, label: (i === 0 ? 'Today' : i === 1 ? 'Tomorrow' : wd) + ', ' + md, slots: slots });
    }
    return days;
  }

  /* ---------- Checkout ---------- */

  function renderCheckout(t) {
    var days = pickupDays();
    var f = state.form;
    if (!days.some(function (d) { return d.iso === f.date; })) f.date = days.length ? days[0].iso : '';
    var day = days.find(function (d) { return d.iso === f.date; });
    var times = day ? day.slots : [];
    if (times.indexOf(Number(f.time)) === -1) f.time = times.length ? String(times[0]) : '';
    return '<div class="cart-head"><button class="back" type="button" data-go="cart">&larr; Back to order</button><button class="sheet-x cart-x" type="button" data-close-cart aria-label="Close">&times;</button></div>' +
      '<h2>Checkout</h2>' +
      '<form class="co" data-checkout novalidate>' +
        '<fieldset><legend>Pickup time</legend>' +
          '<div class="co-row">' +
            '<label>Day<select name="date" required>' + days.map(function (d) { return '<option value="' + d.iso + '"' + (d.iso === f.date ? ' selected' : '') + '>' + esc(d.label) + '</option>'; }).join('') + '</select></label>' +
            '<label>Time<select name="time" required>' + times.map(function (m) { return '<option value="' + m + '"' + (String(m) === f.time ? ' selected' : '') + '>' + label12(m) + '</option>'; }).join('') + '</select></label>' +
          '</div>' +
          '<p class="muted small">Pickup at 1275 Eglinton Ave. East, Mississauga.</p>' +
        '</fieldset>' +
        '<fieldset><legend>Your details</legend>' +
          '<label>Full name<input name="name" autocomplete="name" required value="' + esc(f.name) + '"></label>' +
          '<label>Phone<input name="phone" type="tel" autocomplete="tel" required value="' + esc(f.phone) + '" placeholder="So we can reach you about the order"></label>' +
          '<label>Email<input name="email" type="email" autocomplete="email" required value="' + esc(f.email) + '" placeholder="For your confirmation"></label>' +
          '<label>Order notes <i>Optional</i><textarea name="notes" rows="2" maxlength="500">' + esc(f.notes) + '</textarea></label>' +
        '</fieldset>' +
        '<div class="pay-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18v12H3zm2 2v2h14V8zm0 5v3h14v-3z"/></svg><div><b>Pay at pickup</b><span>Cash, debit or credit card at the counter.</span></div></div>' +
        summaryRows(t) +
        '<label class="agree"><input type="checkbox" name="agree"' + (f.agree ? ' checked' : '') + '> <span>I agree to the <a href="' + esc(cfg.terms) + '" target="_blank" rel="noopener">terms</a> and <a href="' + esc(cfg.cancel) + '" target="_blank" rel="noopener">cancellation policy</a>.</span></label>' +
        (state.error ? '<p class="co-err" role="alert">' + esc(state.error) + '</p>' : '') +
        '<button class="btn btn-solid btn-lg btn-block" type="submit"' + (state.sending ? ' disabled' : '') + '>' + (state.sending ? 'Placing your order…' : 'Place order · ' + fmt(t.total)) + '</button>' +
      '</form>';
  }

  function wireCheckout() {
    var form = box.querySelector('[data-checkout]');
    form.addEventListener('input', function (e) {
      var el = e.target;
      state.form[el.name] = el.type === 'checkbox' ? el.checked : el.value;
      el.removeAttribute('aria-invalid');
    });
    form.addEventListener('change', function (e) {
      if (e.target.name === 'date') { state.form.date = e.target.value; state.form.time = ''; renderCart(); }
      if (e.target.name === 'agree') state.form.agree = e.target.checked;
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      submitOrder(form);
    });
  }

  function submitOrder(form) {
    var f = state.form;
    var bad = null;
    if (!f.name.trim()) bad = ['name', 'Please enter your name.'];
    else if (f.phone.replace(/\D/g, '').length < 10) bad = ['phone', 'Please enter a phone number with area code.'];
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email.trim())) bad = ['email', 'Please enter a valid email address.'];
    else if (!f.date || f.time === '') bad = ['date', 'Please choose a pickup time.'];
    else if (!f.agree) bad = ['agree', 'Please agree to the terms and cancellation policy.'];
    if (bad) {
      state.error = bad[1];
      renderCart();
      var el = box.querySelector('[name="' + bad[0] + '"]');
      if (el) { el.setAttribute('aria-invalid', 'true'); el.focus(); }
      return;
    }
    state.sending = true;
    state.error = '';
    renderCart();
    fetch(cfg.api, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        key: state.key,
        lines: state.cart,
        pickup: { date: f.date, time: Number(f.time) },
        customer: { name: f.name.trim(), phone: f.phone.trim(), email: f.email.trim() },
        notes: f.notes.trim()
      })
    }).then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Unexpected reply from the server.' }; }); })
      .then(function (res) {
        state.sending = false;
        if (!res.ok) {
          state.error = res.error || 'Sorry, we couldn’t place your order.';
          renderCart();
          return;
        }
        state.done = res.order;
        state.view = 'done';
        state.cart = [];
        state.key = newKey();
        saveCart();
        renderCart();
        var h = box.querySelector('h2');
        if (h) { h.setAttribute('tabindex', '-1'); h.focus({ preventScroll: true }); }
        if (cartSheet.hidden && window.innerWidth <= 1080) fab.click();
      })
      .catch(function () {
        state.sending = false;
        state.error = 'We couldn’t reach the bakery. Check your connection and try again, or call ' + cfg.phone + '.';
        renderCart();
      });
  }

  function renderDone() {
    var o = state.done;
    return '<div class="done">' +
      '<div class="done-tick" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg></div>' +
      '<h2>Thank you, ' + esc(o.firstName) + '!</h2>' +
      '<p>Your order <b>' + esc(o.number) + '</b> is in. We’ll have it ready for pickup.</p>' +
      '<div class="done-when"><span>Pickup</span><b>' + esc(o.pickupLabel) + '</b><span>1275 Eglinton Ave. East, Mississauga</span></div>' +
      '<ul class="done-lines">' + o.lines.map(function (l) {
        return '<li><span>' + l.qty + ' × ' + esc(l.name) + (l.detail ? ' <i>' + esc(l.detail) + '</i>' : '') + '</span><b>' + fmt(l.total) + '</b></li>';
      }).join('') + '</ul>' +
      summaryRows({ sub: o.subtotal, tax: o.tax, total: o.total }) +
      '<p class="muted small">Pay at the counter when you pick up. ' + (o.emailed ? 'A confirmation is on its way to ' + esc(o.email) + '.' : '') +
      ' Questions? Call <a href="tel:' + esc(cfg.tel) + '">' + esc(cfg.phone) + '</a>.</p>' +
      '<button class="btn btn-line btn-block" type="button" data-new-order>Start a new order</button>' +
    '</div>';
  }

  /* ---------- Toast ---------- */

  var toastEl;
  function toast(msg) {
    if (!toastEl) {
      toastEl = document.createElement('div');
      toastEl.className = 'toast';
      toastEl.setAttribute('role', 'status');
      document.body.appendChild(toastEl);
    }
    toastEl.textContent = msg;
    toastEl.classList.add('is-in');
    clearTimeout(toast.t);
    toast.t = setTimeout(function () { toastEl.classList.remove('is-in'); }, 2200);
  }

  renderMenu();
  renderCart();
})();
