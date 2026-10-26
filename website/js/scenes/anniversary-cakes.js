/* Anniversary cakes, modelled on La Sem's two-tier sage watercolour cake: white buttercream
   painted with soft green watercolour, piped rope borders, a crown of fresh flowers on top and
   a cascade down the front to the silver board: white gerberas, baby's breath, snapdragons,
   white roses, ferns and leaves. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  var FRONT_T = 10.5;
  k.frame({ look: 0.15, h: 3.6, w: 3.3, elev: 0.45, spinSpeed: 0.06, spinStart: -FRONT_T * 0.06 });
  k.rimColor('#bcdc95');

  /* ---------- Silver board ---------- */
  var silver = new T.MeshStandardMaterial({ color: 0xb4b8be, metalness: 0.7, roughness: 0.28 });
  silver.color.convertSRGBToLinear();
  var boardM = k.shadow(new T.Mesh(new T.CylinderGeometry(1.5, 1.5, 0.04, 96), silver));
  boardM.position.y = -0.88;
  k.spread(boardM, 0.2, 0.5);
  var by = -0.86;

  /* ---------- Watercolour buttercream: painted on stroke by stroke ---------- */
  function watercolour(seed, strength) {
    var ops = [];
    for (var i = 0; i < 46; i++) (function (i) {
      ops.push(function (g, w, h) {
        var y = h * (0.35 + rand(seed + i) * 0.7), x = rand(seed + i + 50) * w, len = 120 + rand(seed + i + 9) * 360;
        var green = ['rgba(150,186,150,', 'rgba(126,168,128,', 'rgba(184,210,180,', 'rgba(108,150,112,'][i % 4];
        g.strokeStyle = green + (0.18 + rand(seed + i + 3) * 0.3) * strength + ')';
        g.lineWidth = 10 + rand(seed + i + 4) * 46;
        g.lineCap = 'round';
        g.beginPath(); g.moveTo(x, y);
        g.bezierCurveTo(x + len * 0.3, y - 20 + rand(i) * 40, x + len * 0.7, y - 30 + rand(i + 1) * 60, x + len, y + (rand(i + 2) - 0.5) * 50);
        g.stroke();
      });
    })(i);
    return k.painter(1024, 256, function (g, w, h) {
      g.fillStyle = '#fbfcfa'; g.fillRect(0, 0, w, h);
      var grd = g.createLinearGradient(0, 0, 0, h);
      grd.addColorStop(0, 'rgba(255,255,255,0)'); grd.addColorStop(1, 'rgba(160,192,158,' + 0.25 * strength + ')');
      g.fillStyle = grd; g.fillRect(0, 0, w, h);
    }, ops, { wrap: true });
  }
  var paintBottom = watercolour(11, 1), paintTop = watercolour(77, 1.25);
  var matBottom = new T.MeshStandardMaterial({ map: paintBottom.tex, roughness: 0.5 });
  var matTop = new T.MeshStandardMaterial({ map: paintTop.tex, roughness: 0.5 });

  k.cap(0.3, 'Two tiers of sponge');
  var top1 = k.tier({ r: 0.95, h: 0.56, y: by, t0: 0.4, mat: matBottom, gap: 0.7 });
  var top2 = k.tier({ r: 0.63, h: 0.74, y: top1, t0: 1.7, mat: matTop, gap: 0.7 });
  var R1 = 0.985, R2 = 0.665;
  k.cap(3.0, 'Painted with sage watercolour');
  k.reveal(paintBottom, 3.0, 1.4);
  k.reveal(paintTop, 3.2, 1.4);

  // Piped rope borders: little tilted ovals that read as a twisted rope.
  k.cap(4.5, 'Piped rope borders');
  function rope(r, y, n, t0, mat) {
    var g = new T.SphereGeometry(0.03, 10, 8);
    for (var i = 0; i < n; i++) {
      var a = (i / n) * Math.PI * 2, m = k.shadow(new T.Mesh(g, mat));
      m.scale.set(1.5, 0.75, 0.75);
      var h = k.group(); h.add(m);
      m.rotation.z = 0.7;
      h.rotation.y = -a + Math.PI / 2;
      h.position.set(Math.cos(a) * r, y + 0.025, Math.sin(a) * r);
      k.pop(h, t0 + i * 0.012, 0.25);
    }
  }
  rope(R2 + 0.015, top1, 52, 4.5, k.mat(0xfbfbf8, { roughness: 0.5 }));
  rope(R1 + 0.015, by, 70, 4.8, k.mat(0x8e9a92, { roughness: 0.45 }));

  /* ---------- Flower pieces ---------- */
  var white = k.mat(0xfdfdfb, { roughness: 0.55, side: T.DoubleSide });
  var greenLeaf = k.gloss(0x3e6e33, { roughness: 0.4, clearcoat: 0.3, side: T.DoubleSide });
  var stemMat = k.mat(0x5e8a3e, { roughness: 0.6 });
  var petalShape = new T.Shape();
  petalShape.moveTo(-0.01, 0); petalShape.quadraticCurveTo(-0.017, 0.08, 0, 0.145); petalShape.quadraticCurveTo(0.017, 0.08, 0.01, 0);
  var petalGeo = new T.ShapeGeometry(petalShape, 6);
  function gerbera(s) {
    var g = k.group();
    [[28, 0, 1, 0.1], [24, 0.11, 0.8, 0.3], [18, 0.2, 0.55, 0.6]].forEach(function (L) {
      for (var i = 0; i < L[0]; i++) {
        var a = (i / L[0]) * Math.PI * 2 + L[1], hol = k.group(), p = new T.Mesh(petalGeo, white);
        p.rotation.x = -Math.PI / 2 + L[3];
        p.scale.setScalar(L[2]);
        hol.add(p);
        hol.rotation.y = -a + Math.PI / 2;
        g.add(hol);
      }
    });
    var disc = new T.Mesh(new T.SphereGeometry(0.032, 18, 10), k.mat(0xbfcf4a, { roughness: 0.8 }));
    disc.scale.set(1, 0.45, 1);
    disc.position.y = 0.012;
    var ring = new T.Mesh(new T.TorusGeometry(0.034, 0.008, 8, 24), k.mat(0x7f9a2e, { roughness: 0.8 }));
    ring.rotation.x = Math.PI / 2;
    ring.position.y = 0.012;
    g.add(disc, ring);
    g.scale.setScalar(s);
    g.traverse(function (c) { c.castShadow = true; });
    return g;
  }
  function babysBreath(s, seed) {
    var g = k.group(), dot = new T.SphereGeometry(0.011, 6, 5);
    for (var i = 0; i < 28; i++) {
      var a = rand(seed + i) * Math.PI * 2, r = Math.sqrt(rand(seed + i + 40)) * 0.11, hgt = 0.03 + (0.11 - r) * 0.5;
      var d = new T.Mesh(dot, white);
      d.position.set(Math.cos(a) * r, hgt, Math.sin(a) * r);
      g.add(d);
      if (i % 4 === 0) {
        var st = new T.Mesh(new T.CylinderGeometry(0.002, 0.002, hgt, 4), stemMat);
        st.position.set(Math.cos(a) * r * 0.5, hgt / 2, Math.sin(a) * r * 0.5);
        st.rotation.set(Math.sin(a) * r * 3, 0, -Math.cos(a) * r * 3);
        g.add(st);
      }
    }
    g.scale.setScalar(s);
    return g;
  }
  var bell = new T.LatheGeometry([0, 0.01, 0.022, 0.026, 0.024].map(function (r, i) { return new T.Vector2(r + 0.001, i * 0.012); }), 12);
  function snapdragon(len, seed) {
    var g = k.group();
    var stem = new T.Mesh(new T.CylinderGeometry(0.006, 0.008, len, 6), stemMat);
    stem.position.y = len / 2;
    g.add(stem);
    var n = 10;
    for (var i = 0; i < n; i++) {
      var q = i / n, b = new T.Mesh(bell, q > 0.7 ? k.mat(0xb9d48c, { roughness: 0.6 }) : k.mat(0xf1f4e6, { roughness: 0.6 }));
      var a = i * 2.2 + seed;
      b.scale.setScalar(1.5 - q * 0.9);
      b.position.set(Math.cos(a) * 0.022, len * (0.2 + q * 0.8), Math.sin(a) * 0.022);
      b.rotation.set(Math.sin(a) * 0.8, 0, -Math.cos(a) * 0.8);
      g.add(b);
    }
    return g;
  }
  function fern(len) {
    var g = k.group();
    var stem = new T.Mesh(new T.CylinderGeometry(0.004, 0.006, len, 5), stemMat);
    stem.position.y = len / 2;
    g.add(stem);
    for (var i = 0; i < 14; i++) {
      var q = i / 14;
      [-1, 1].forEach(function (s) {
        var lf = new T.Mesh(k.G.mint, greenLeaf);
        lf.scale.set(0.25 * (1 - q * 0.7), 0.42 * (1 - q * 0.7), 0.25);
        lf.position.set(s * 0.025 * (1 - q), len * (0.12 + q * 0.85), 0);
        lf.rotation.set(0, 0, -s * 1.2);
        g.add(lf);
      });
    }
    return g;
  }

  // Place a piece on the cake (or the board). d = angle from the front, + to the viewer's right.
  function place(obj, d, y, r, opts) {
    opts = opts || {};
    var a = Math.PI / 2 - d, holder = k.group();
    if (!opts.up) { obj.rotation.z = -Math.PI / 2 + (opts.tilt == null ? 0.35 : opts.tilt); holder.rotation.y = -a; }
    else { obj.rotation.set(opts.lean || 0, opts.spin || 0, 0); }
    holder.add(obj);
    holder.position.set(Math.cos(a) * r, y, Math.sin(a) * r);
    k.pop(holder, opts.t0, opts.dur || 0.45);
  }

  /* ---------- Crown on top and cascade down the front ---------- */
  // Cascade path: from the top tier's right shoulder down across the front to the board.
  function onTier(s) {
    if (s < 0.5) { var q = s / 0.5; return { d: 0.3 - q * 0.28, y: top2 - 0.08 - q * (top2 - top1 - 0.12), r: R2 + 0.04 }; }
    var q2 = (s - 0.5) / 0.5;
    return { d: 0.04 - q2 * 0.5, y: top1 - 0.08 - q2 * (top1 - by - 0.2), r: R1 + 0.04 };
  }

  k.cap(5.1, 'Ferns and leaves');
  for (var i = 0; i < 46; i++) {
    var s = rand(i + 7), p = onTier(s), lf = new T.Mesh(k.G.mint, greenLeaf);
    lf.scale.setScalar(1.9 + rand(i) * 0.9);
    lf.rotation.y = (rand(i + 2) - 0.5) * 2.5;
    var edge = (i % 2 ? 1 : -1) * (0.28 + rand(i + 3) * 0.22);
    place(lf, p.d + edge, p.y + (rand(i + 4) - 0.5) * 0.2, p.r + 0.03, { t0: 5.2 + s * 1.1, tilt: 0.1 });
  }
  [[-0.5, 0.0], [0.9, 0.2], [0.45, -0.25], [-0.15, 0.55]].forEach(function (f, i) {
    var fr = fern(0.5 + rand(i) * 0.2);
    fr.rotation.set(1.0, 0, 0);
    var h = k.group(); h.add(fr);
    h.rotation.y = Math.atan2(f[0], f[1] + 0.001) + Math.PI;
    h.position.set(f[0] * 0.5, top2, f[1] * 0.5);
    k.pop(h, 5.6 + i * 0.15, 0.5);
  });
  // Ferns and leaves on the board at the front.
  [[-0.9, 0.95], [0.45, 1.25], [-0.25, 1.3]].forEach(function (f, i) {
    var fr = fern(0.55);
    fr.rotation.set(1.35, 0, 0);
    var h = k.group(); h.add(fr);
    h.rotation.y = (rand(i) - 0.5) * 1.5;
    h.position.set(f[0], by + 0.02, f[1] - 0.15);
    k.pop(h, 6.0 + i * 0.15, 0.5);
  });

  k.cap(6.4, 'White gerberas');
  var gerb = 0;
  for (var gi = 0; gi < 11; gi++, gerb++) {
    var sg = gi / 10, pg = onTier(sg);
    place(gerbera(2.0 + rand(gi) * 0.4), pg.d + ((gi % 3) - 1) * 0.26, pg.y + (rand(gi + 5) - 0.5) * 0.12, pg.r + 0.06, { t0: 6.5 + sg * 1.6, tilt: 0.12 });
  }
  // Two on the crown and two on the board.
  [[0.1, 0.25, 0.3], [-0.25, -0.05, -0.2]].forEach(function (c, i) {
    var g = gerbera(1.6);
    g.rotation.set(c[2], 0, 0.25 * (i ? -1 : 1));
    var h = k.group(); h.add(g); h.position.set(c[0], top2 + 0.05, c[1]);
    k.pop(h, 6.3 + i * 0.2, 0.45);
  });
  [[-0.45, 1.12], [0.15, 1.18]].forEach(function (c, i) {
    var g = gerbera(1.7);
    g.rotation.set(-0.9, 0, 0);
    var h = k.group(); h.add(g); h.position.set(c[0], by + 0.12, c[1]);
    k.pop(h, 8.1 + i * 0.2, 0.45);
  });

  k.cap(7.6, 'White roses');
  for (var ri = 0; ri < 4; ri++) {
    var sr = rand(ri + 30), pr = onTier(sr);
    place(k.rose({ color: 0xfbfbf6, scale: 0.95 }), pr.d + (rand(ri) - 0.5) * 0.6, pr.y, pr.r + 0.02, { t0: 7.7 + ri * 0.12 });
  }

  k.cap(8.5, 'Clouds of baby’s breath');
  for (var b = 0; b < 16; b++) {
    var sb = rand(b + 60), pb = onTier(sb);
    place(babysBreath(1.05 + rand(b) * 0.4, b * 13), pb.d + (rand(b + 1) - 0.5) * 0.8, pb.y + (rand(b + 2) - 0.5) * 0.15, pb.r + 0.03, { t0: 8.6 + rand(b + 3) * 0.8, tilt: 0.5 });
  }
  [[0.2, -0.2], [-0.1, 0.25], [0.3, 0.2], [-0.35, 0.0], [0.0, 0.0], [-0.2, -0.3], [0.4, -0.05]].forEach(function (c, i) {
    var bb = babysBreath(1.45, 300 + i * 9);
    var h = k.group(); h.add(bb); h.position.set(c[0], top2 + 0.02, c[1]);
    k.pop(h, 8.7 + i * 0.1, 0.4);
  });

  k.cap(9.3, 'Snapdragons');
  [[0.55, 0.15, -0.7, 0.55], [-0.5, 0.05, 0.8, 0.5], [0.15, -0.3, -0.25, 0.6], [1.05, 0.65, -1.2, 0.5], [-1.0, 0.9, 1.3, 0.55]].forEach(function (c, i) {
    var sd = snapdragon(c[3], i * 3);
    sd.rotation.set(0, 0, c[2]);
    var h = k.group(); h.add(sd);
    var onBoard = Math.abs(c[0]) > 0.9;
    h.position.set(c[0] * (onBoard ? 0.8 : 0.9), onBoard ? by + 0.02 : top2, c[1]);
    k.grow(h, 9.4 + i * 0.15, 0.6);
  });
  // A few fine grass sprigs rising from the crown.
  var grass = k.mat(0x3c4a2c, { roughness: 0.7 });
  for (var gs = 0; gs < 9; gs++) {
    var pts = [], ga = rand(gs) * 6;
    for (var q = 0; q < 5; q++) pts.push(new T.Vector3(Math.cos(ga) * q * 0.05, q * 0.11, Math.sin(ga) * q * 0.05));
    var gm = new T.Mesh(new T.TubeGeometry(new T.CatmullRomCurve3(pts), 12, 0.003, 4, false), grass);
    gm.position.set((rand(gs + 3) - 0.5) * 0.4, top2 + 0.05, (rand(gs + 4) - 0.5) * 0.3);
    k.grow(gm, 9.9 + gs * 0.05, 0.5);
  }

  k.sparkle({ t: 10.3, y: top1, r: 1.1, n: 40, color: 0xf4ffe8 });
  k.cap(10.4, 'Every year worth celebrating');
  k.end(12);
});
