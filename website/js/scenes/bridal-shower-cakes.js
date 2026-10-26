/* Bridal shower cakes, modelled on La Sem's Delft-blue cake: three white tiers (the top one
   six-sided) hand-painted in blue and white with roses, vines, a bird and a Delft tile, blue
   rope borders, and deep red sugar peonies with berry sprays on the top and middle tiers. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  var FRONT_T = 10.5;
  k.frame({ look: 0.25, h: 3.9, w: 3.2, elev: 0.5, spinSpeed: 0.06, spinStart: -FRONT_T * 0.06 });
  k.rimColor('#f08a8a');

  var silver = new T.MeshStandardMaterial({ color: 0xb4b8be, metalness: 0.7, roughness: 0.28 });
  silver.color.convertSRGBToLinear();
  var boardM = k.shadow(new T.Mesh(new T.CylinderGeometry(1.32, 1.32, 0.04, 96), silver));
  boardM.position.y = -0.88;
  k.spread(boardM, 0.2, 0.5);
  var by = -0.86;

  /* ---------- Hand-painted Delft pattern, one painter per tier ---------- */
  // The texture's middle (u = 0.5) faces the viewer. sx keeps painted shapes round on the cake.
  function delft(r, h, seed, extra) {
    var W = 2048, H = 512, sx = (W / H) / ((2 * Math.PI * r) / h);
    var ops = [];
    function at(g, x, y) { g.setTransform(sx, 0, 0, 1, x, y); }
    function rose(x, y, s, light) {
      ops.push(function (g) {
        at(g, x, y);
        g.fillStyle = light ? '#9fb9e3' : '#6f8fca';
        g.beginPath(); g.arc(0, 0, 46 * s, 0, 7); g.fill();
        g.lineWidth = 5 * s; g.strokeStyle = '#2d4c8e';
        for (var i = 0; i < 5; i++) { g.beginPath(); g.arc((i % 2 ? 6 : -6) * s, (i - 2) * 5 * s, (40 - i * 8) * s, Math.PI * (0.1 + i * 0.3), Math.PI * (1.2 + i * 0.3)); g.stroke(); }
        g.strokeStyle = 'rgba(255,255,255,.75)'; g.lineWidth = 3 * s;
        for (var j = 0; j < 4; j++) { g.beginPath(); g.arc(0, 0, (34 - j * 9) * s, Math.PI * (1.1 + j * 0.4), Math.PI * (1.6 + j * 0.4)); g.stroke(); }
      });
    }
    function vine(x, y, len, dir) {
      ops.push(function (g) {
        at(g, x, y);
        g.strokeStyle = '#1b2d5e'; g.lineWidth = 18; g.lineCap = 'round';
        g.beginPath(); g.moveTo(0, 0); g.bezierCurveTo(dir * len * 0.3, -len * 0.4, dir * len * 0.7, len * 0.2, dir * len, -len * 0.5); g.stroke();
        g.fillStyle = '#1b2d5e';
        for (var i = 1; i < 6; i++) {
          var t = i / 6, lx = dir * len * t, ly = -len * 0.25 * Math.sin(t * Math.PI * 1.4);
          g.save(); g.translate(lx, ly); g.rotate(dir * (i % 2 ? 0.9 : -0.9) - 0.3);
          g.beginPath(); g.ellipse(0, -36, 18, 44, 0, 0, 7); g.fill(); g.restore();
        }
      });
    }
    var n = 12, i;
    for (i = 0; i < n; i++) {
      var x = ((i + 0.2 + rand(seed + i) * 0.6) / n) * W, y = H * (0.3 + rand(seed + i + 9) * 0.4);
      vine(x - 60, H * 0.9, 300 + rand(seed + i + 2) * 120, i % 2 ? 1 : -1);
      vine(x + 40, H * 0.15, 260, i % 2 ? -1 : 1);
      if (extra && Math.abs(x - W / 2) < W * 0.12) continue;
      rose(x, y, 3.0 + rand(seed + i + 3) * 1.0, i % 3 === 0);
      rose(x + 140, H * (i % 2 ? 0.82 : 0.2), 1.7, i % 2 === 0);
    }
    if (extra) extra(ops, at, W, H, sx);
    return k.painter(W, H, function (g, w, hh) {
      g.setTransform(1, 0, 0, 1, 0, 0);
      g.fillStyle = '#fbfaf6'; g.fillRect(0, 0, w, hh);
    }, ops, { wrap: true });
  }
  // Delft tile on the front of the bottom tier.
  function tile(ops, at, W, H) {
    ops.push(function (g) {
      at(g, W / 2, H * 0.55);
      g.scale(1.3, 1.3);
      g.fillStyle = '#f3efe2'; g.fillRect(-150, -170, 300, 340);
      g.strokeStyle = '#2d4c8e'; g.lineWidth = 12; g.strokeRect(-140, -160, 280, 320);
      g.lineWidth = 4; g.strokeRect(-122, -142, 244, 284);
      g.fillStyle = '#3f62a8';
      g.beginPath(); g.moveTo(-40, 120); g.quadraticCurveTo(-60, 60, -20, 40); g.lineTo(20, 40); g.quadraticCurveTo(60, 60, 40, 120); g.closePath(); g.fill();
      for (var i = 0; i < 9; i++) { var a = -Math.PI * 0.95 + i * 0.24; g.beginPath(); g.arc(Math.cos(a) * 70, -20 + Math.sin(a) * 60, 26, 0, 7); g.fillStyle = i % 2 ? '#6f8fca' : '#2d4c8e'; g.fill(); }
    });
  }
  // A bird with spread wings on the front of the middle tier.
  function bird(ops, at, W, H) {
    ops.push(function (g) {
      at(g, W / 2, H * 0.55);
      g.scale(1.8, 1.8);
      g.fillStyle = '#8fabda';
      g.beginPath(); g.ellipse(0, 30, 70, 34, -0.3, 0, 7); g.fill();
      g.beginPath(); g.arc(-70, 0, 26, 0, 7); g.fill();
      g.fillStyle = '#c9d8f0';
      for (var i = 0; i < 7; i++) { g.save(); g.rotate(-0.9 + i * 0.16); g.beginPath(); g.ellipse(40, -70, 16, 80, 0, 0, 7); g.fill(); g.restore(); }
      g.strokeStyle = '#2d4c8e'; g.lineWidth = 4;
      for (var j = 0; j < 7; j++) { g.save(); g.rotate(-0.9 + j * 0.16); g.beginPath(); g.ellipse(40, -70, 16, 80, 0, 0, 7); g.stroke(); g.restore(); }
      g.fillStyle = '#1b2d5e'; g.beginPath(); g.arc(-78, -4, 5, 0, 7); g.fill();
      g.beginPath(); g.moveTo(80, 40); g.lineTo(160, 70); g.lineTo(150, 30); g.closePath(); g.fill();
    });
  }

  /* ---------- Tiers ---------- */
  var plan = [
    { r: 0.95, h: 0.58, seg: 96, extra: tile, t: 0.4 },
    { r: 0.72, h: 0.48, seg: 96, extra: bird, t: 1.7 },
    { r: 0.5, h: 0.5, seg: 6, extra: null, t: 3.0 }
  ];
  var y = by, tiers = [];
  k.cap(0.3, 'Three tiers of sponge');
  plan.forEach(function (p, i) {
    var top = k.sponge({ r: p.r, h: p.h, y: y, t0: p.t, height: 3.6 });
    var paint = delft(p.r + 0.035, p.h + 0.025, 40 + i * 37, p.extra);
    var cr = p.seg < 10 ? (p.r + 0.04) / Math.cos(Math.PI / p.seg) : p.r + 0.035;
    var geo = new T.CylinderGeometry(cr, cr, p.h + 0.025, p.seg, 1);
    geo.translate(0, (p.h + 0.025) / 2, 0);
    var mat = new T.MeshStandardMaterial({ map: paint.tex, roughness: 0.45, flatShading: p.seg < 10 });
    var plain = k.mat(0xfbfaf6, { roughness: 0.45 });
    var coat = k.shadow(new T.Mesh(geo, [mat, plain, plain]));
    coat.rotation.y = Math.PI + (p.seg < 10 ? Math.PI / 6 : 0);
    coat.position.y = y;
    k.rise(coat, p.t + 0.7, 0.6);
    tiers.push({ r: p.seg < 10 ? (p.r + 0.04) : p.r + 0.035, y0: y, y1: top + 0.025, paint: paint });
    y = top + 0.025;
  });
  if (plan[0]) k.cap(1.1, 'Smooth white buttercream');

  k.cap(4.2, 'Hand-painted Delft blue');
  tiers.forEach(function (tr, i) { k.reveal(tr.paint, 4.2 + i * 0.3, 2.0); });

  // Blue rope borders at the base of each tier.
  k.cap(6.6, 'Blue rope borders');
  var ropeMat = k.gloss(0x4a68a8, { roughness: 0.35, clearcoat: 0.4 });
  var bead = new T.SphereGeometry(0.032, 10, 8);
  tiers.forEach(function (tr, ti) {
    var n = Math.round(tr.r * 70);
    for (var i = 0; i < n; i++) {
      var a = (i / n) * Math.PI * 2, m = k.shadow(new T.Mesh(bead, ropeMat));
      m.scale.set(1.5, 0.8, 0.8);
      m.rotation.z = 0.7;
      var h = k.group(); h.add(m);
      h.rotation.y = -a + Math.PI / 2;
      h.position.set(Math.cos(a) * (tr.r + 0.012), tr.y0 + 0.028, Math.sin(a) * (tr.r + 0.012));
      k.pop(h, 6.6 + ti * 0.25 + i * 0.006, 0.25);
    }
  });

  /* ---------- Red sugar peonies, buds and berry sprays ---------- */
  var red = k.gloss(0x9e1a30, { roughness: 0.45, clearcoat: 0.3, side: T.DoubleSide });
  var redDeep = k.gloss(0x7a1022, { roughness: 0.45, clearcoat: 0.3, side: T.DoubleSide });
  var petalShape = new T.Shape();
  petalShape.moveTo(0, 0);
  petalShape.bezierCurveTo(0.09, 0.03, 0.1, 0.13, 0.0, 0.16);
  petalShape.bezierCurveTo(-0.1, 0.13, -0.09, 0.03, 0, 0);
  var petalGeo = new T.ShapeGeometry(petalShape, 14);
  (function () {
    var p = petalGeo.attributes.position;
    for (var i = 0; i < p.count; i++) { var x = p.getX(i), yy = p.getY(i); p.setZ(i, x * x * 3 + yy * yy * 0.8 + Math.sin(Math.atan2(yy, x) * 9) * 0.008 * yy * 6); }
    petalGeo.computeVertexNormals();
  })();
  function peony(s) {
    var g = k.group();
    [[5, 0.0, 0.25, 0.55, redDeep], [6, 0.5, 0.55, 0.75, redDeep], [7, 0.2, 0.85, 0.95, red], [8, 0.7, 1.15, 1.05, red]].forEach(function (L) {
      for (var i = 0; i < L[0]; i++) {
        var a = (i / L[0]) * Math.PI * 2 + L[1], hol = k.group(), p = new T.Mesh(petalGeo, L[4]);
        p.rotation.x = -Math.PI / 2 + (Math.PI / 2 - L[2] * 0.75);
        p.scale.setScalar(L[3]);
        hol.add(p);
        hol.rotation.y = -a + Math.PI / 2;
        g.add(hol);
      }
    });
    g.scale.setScalar(s);
    g.traverse(function (c) { c.castShadow = true; });
    return g;
  }
  var stem = k.mat(0x5a1520, { roughness: 0.6 });
  function berrySpray(seed) {
    var g = k.group(), berry = new T.SphereGeometry(0.026, 12, 10);
    for (var i = 0; i < 5; i++) {
      var a = rand(seed + i) * 6, len = 0.25 + rand(seed + i + 3) * 0.25;
      var tip = new T.Vector3(Math.cos(a) * 0.12, len, Math.sin(a) * 0.12);
      var st = new T.Mesh(new T.TubeGeometry(new T.CatmullRomCurve3([new T.Vector3(0, 0, 0), tip.clone().multiplyScalar(0.5).add(new T.Vector3(0, 0.03, 0)), tip]), 8, 0.004, 4, false), stem);
      g.add(st);
      var b = k.shadow(new T.Mesh(berry, red));
      b.position.copy(tip);
      g.add(b);
      if (i % 2) { var bud = k.shadow(new T.Mesh(new T.ConeGeometry(0.02, 0.07, 10), red)); bud.position.copy(tip).add(new T.Vector3(0, 0.03, 0)); g.add(bud); }
    }
    return g;
  }

  k.cap(7.6, 'Deep red sugar peonies');
  var top3 = tiers[2].y1, top2 = tiers[1].y1;
  // On the top tier, towards the viewer's left.
  [[-0.22, 0.05, 2.6, 0.25], [0.18, -0.06, 2.2, -0.25], [-0.5, -0.12, 2.0, 0.6], [0.06, 0.26, 1.9, -0.45], [0.36, 0.18, 1.7, -0.7]].forEach(function (p, i) {
    var pe = peony(p[2]);
    pe.rotation.set(p[3] * 0.6, rand(i) * 6, p[3]);
    k.drop(pe, 7.7 + i * 0.22, 0.6, [p[0], top3 + 0.02, p[1]], { spin: 0.5, height: 2 });
  });
  // On the middle tier, at the viewer's right.
  [[0.62, 0.4, 2.5], [0.8, 0.05, 2.1], [0.45, 0.6, 1.9], [0.85, 0.4, 1.7]].forEach(function (p, i) {
    var pe = peony(p[2]);
    pe.rotation.set(0.3, rand(i + 4) * 6, -0.9);
    var h = k.group(); h.add(pe);
    h.position.set(p[0], top2 + 0.02, p[1]);
    k.pop(h, 8.6 + i * 0.22, 0.5);
  });
  k.cap(9.4, 'Berry sprays and buds');
  [[-0.1, top3, 0.0, 0.0], [0.25, top3, 0.1, -0.3], [0.85, top2, 0.25, -0.6], [0.7, top2, 0.5, -0.4]].forEach(function (p, i) {
    var bs = berrySpray(i * 11);
    bs.rotation.set(0, 0, p[3]);
    var h = k.group(); h.add(bs); h.position.set(p[0], p[1], p[2]);
    k.grow(h, 9.5 + i * 0.15, 0.6);
  });
  k.sparkle({ t: 10.2, y: top2, r: 1.0, n: 38, color: 0xffd0d0 });
  k.cap(10.4, 'Ready for the shower');
  k.end(12);
});
