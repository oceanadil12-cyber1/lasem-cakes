/* Wedding cakes, modelled on La Sem's five-tier white wedding cake: a black board,
   five fondant tiers of different heights, hand-painted black marble veins,
   and a cascade of white sugar anemones, blush roses, dark leaves and tiny blossoms running
   from the fourth tier down to the bottom. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  k.frame({ look: 1.75, h: 4.3, w: 2.4, elev: 0.6, spinSpeed: 0.08 });
  k.floor(0); // the board sits straight on the counter (no pedestal)
  k.rimColor('#e6c88e');

  var fondant = k.mat(0xfbf8f3, { roughness: 0.4 });
  var ink = k.mat(0x1b1a1c, { roughness: 0.5 });
  var petalWhite = k.mat(0xfdfbf7, { roughness: 0.55, side: T.DoubleSide });
  var blush = k.gloss(0xf1c3b2, { roughness: 0.5, clearcoat: 0.25, side: T.DoubleSide });
  var leafMat = k.gloss(0x1e2a22, { roughness: 0.35, clearcoat: 0.4, side: T.DoubleSide });
  var black = k.mat(0x0b0b0c, { roughness: 0.8, envMapIntensity: 0.3 });
  var eyeMat = k.mat(0x0e0d0f, { roughness: 0.6 });

  /* ---------- Cake board ---------- */
  k.cap(0.1, 'A black cake board');
  var board = k.shadow(new T.Mesh(new T.CylinderGeometry(1.07, 1.07, 0.05, 96), black));
  board.position.y = 0.025;
  k.spread(board, 0.3, 0.6);

  /* ---------- Five tiers (proportions taken from the photo) ---------- */
  var dims = [[0.95, 0.82], [0.8, 0.8], [0.63, 0.57], [0.43, 0.76], [0.31, 0.6]];
  var tiers = [], y = 0.05, t = 1.3;
  k.cap(1.2, 'Five tiers of European sponge');
  dims.forEach(function (d, i) {
    var top = k.tier({ r: d[0], h: d[1], y: y, t0: t, mat: fondant, gap: 0.55, coatDur: 0.6, height: 3.8 });
    tiers.push({ r: d[0] + 0.035, y0: y, y1: top });
    if (i === 0) k.cap(1.9, 'Smooth white fondant');
    y = top;
    t += 1.05;
  });
  var cakeTop = y;

  function tierAt(h) {
    for (var i = 0; i < tiers.length; i++) if (h <= tiers[i].y1) return tiers[i];
    return tiers[tiers.length - 1];
  }
  // Angle around the cake measured from the side that faces the viewer at the end;
  // positive offsets are to the viewer's right.
  var front = k.front(14);
  function around(offset) { return front - offset; }

  /* ---------- Hand-painted marble veins (they draw themselves on) ---------- */
  k.cap(6.9, 'Hand-painted black marble veins');
  // [tier, angle from the front (+ = viewer's right), how far down it runs]
  var veinPlan = [[0, 0.62, 0.95], [0, 0.95, 0.55], [0, -1.15, 0.9], [0, 2.2, 0.7], [1, -0.85, 0.95], [1, 1.15, 0.6], [1, 2.6, 0.8],
    [2, -1.0, 0.7], [3, -0.6, 0.95], [3, 1.6, 0.6], [4, -0.5, 0.95], [4, 0.55, 0.75], [4, 1.35, 0.5], [4, 2.8, 0.6]];
  veinPlan.forEach(function (v, i) {
    var tr = tiers[v[0]], a = around(v[1]), h = tr.y1 - 0.01, pts = [];
    var stop = tr.y1 - (tr.y1 - tr.y0) * v[2];
    while (h > stop) {
      pts.push(new T.Vector3(Math.cos(a) * (tr.r + 0.004), h, Math.sin(a) * (tr.r + 0.004)));
      h -= 0.035 + rand(i * 31 + pts.length) * 0.04;
      a += (rand(i * 17 + pts.length) - 0.5) * 0.16 / Math.max(tr.r, 0.4);
    }
    if (pts.length < 3) return;
    var geo = new T.TubeGeometry(new T.CatmullRomCurve3(pts), pts.length * 6, 0.0075, 5, false);
    var vein = new T.Mesh(geo, ink);
    var total = geo.index.count;
    geo.setDrawRange(0, 0);
    k.anim(vein, 7.0 + i * 0.13, 0.9, function (q) { geo.setDrawRange(0, Math.floor(total * k.easeOut(q) / 3) * 3); });
    // A short branch off the main vein.
    var b0 = pts[Math.floor(pts.length * 0.4)];
    if (b0) {
      var ba = Math.atan2(b0.z, b0.x), bp = [b0.clone()], dir = i % 2 ? -1 : 1;
      for (var j = 1; j < 6; j++) { ba += dir * 0.07 / Math.max(tr.r, 0.4); bp.push(new T.Vector3(Math.cos(ba) * (tr.r + 0.004), b0.y - j * 0.04, Math.sin(ba) * (tr.r + 0.004))); }
      var bg = new T.TubeGeometry(new T.CatmullRomCurve3(bp), 24, 0.0055, 5, false);
      var bm = new T.Mesh(bg, ink), bt = bg.index.count;
      bg.setDrawRange(0, 0);
      k.anim(bm, 7.6 + i * 0.13, 0.6, function (q) { bg.setDrawRange(0, Math.floor(bt * q / 3) * 3); });
    }
  });

  /* ---------- Flower pieces ---------- */
  var petalShape = new T.Shape();
  petalShape.moveTo(0, 0);
  petalShape.bezierCurveTo(0.06, 0.02, 0.07, 0.1, 0.0, 0.13);
  petalShape.bezierCurveTo(-0.07, 0.1, -0.06, 0.02, 0, 0);
  var petalGeo = new T.ShapeGeometry(petalShape, 12);
  (function () {
    var p = petalGeo.attributes.position;
    for (var i = 0; i < p.count; i++) { var x = p.getX(i), yy = p.getY(i); p.setZ(i, x * x * 3.5 + yy * yy * 0.9); }
    petalGeo.computeVertexNormals();
  })();
  // White anemone: two layers of broad petals around a black centre with stamens. Faces +y.
  function anemone(s) {
    var g = k.group();
    [[8, 0, 0.32, 1], [6, 0.4, 0.62, 0.82]].forEach(function (layer) {
      for (var i = 0; i < layer[0]; i++) {
        var a = (i / layer[0]) * Math.PI * 2 + layer[1];
        var holder = k.group(), p = new T.Mesh(petalGeo, petalWhite);
        p.rotation.x = -Math.PI / 2 + layer[2];
        p.scale.setScalar(layer[3]);
        holder.add(p);
        holder.rotation.y = -a + Math.PI / 2;
        holder.position.y = 0.004 * layer[3];
        g.add(holder);
      }
    });
    var eye = new T.Mesh(new T.SphereGeometry(0.03, 16, 12), eyeMat);
    eye.scale.set(1, 0.6, 1);
    eye.position.y = 0.035;
    g.add(eye);
    for (var j = 0; j < 14; j++) {
      var st = new T.Mesh(new T.SphereGeometry(0.008, 6, 5), ink), sa = j * 0.45;
      st.position.set(Math.cos(sa) * 0.045, 0.03, Math.sin(sa) * 0.045);
      g.add(st);
    }
    g.scale.setScalar(s);
    g.traverse(function (c) { c.castShadow = true; });
    return g;
  }
  // Sugar rose: rings of small cupped petals, upright in the centre and opening outward, deeper in the middle.
  var blushDeep = k.gloss(0xe7a996, { roughness: 0.5, clearcoat: 0.25, side: T.DoubleSide });
  function sugarRose(s) {
    var g = k.group();
    var bud = new T.Mesh(new T.SphereGeometry(0.03, 12, 10), blushDeep);
    bud.scale.set(1, 1.3, 1);
    bud.position.y = 0.04;
    g.add(bud);
    [[4, 0.3, 0.04, blushDeep, 0.018], [5, 0.38, 0.16, blushDeep, 0.022], [6, 0.46, 0.34, blush, 0.028], [7, 0.52, 0.56, blush, 0.034], [8, 0.56, 0.8, blush, 0.04]].forEach(function (ring, ri) {
      for (var i = 0; i < ring[0]; i++) {
        var a = (i / ring[0]) * Math.PI * 2 + ri * 0.7;
        var holder = k.group(), p = new T.Mesh(petalGeo, ring[3]);
        p.rotation.x = -Math.PI / 2 + (Math.PI / 2 - ring[2]);
        p.scale.set(ring[1] * 1.1, ring[1], ring[1]);
        p.position.z = -ring[4] * 0.2;
        holder.add(p);
        holder.rotation.y = -a + Math.PI / 2;
        holder.position.set(Math.cos(a) * ring[4] * 0.3, 0.005, Math.sin(a) * ring[4] * 0.3);
        g.add(holder);
      }
    });
    g.scale.setScalar(s);
    g.traverse(function (c) { c.castShadow = true; });
    return g;
  }
  // Tiny five-petal blossom.
  function blossom(s) {
    var g = k.group(), pg = new T.SphereGeometry(0.018, 8, 6);
    for (var i = 0; i < 5; i++) {
      var p = new T.Mesh(pg, petalWhite), a = i * Math.PI * 0.4;
      p.scale.set(1, 0.35, 0.7);
      p.position.set(Math.cos(a) * 0.018, 0, Math.sin(a) * 0.018);
      p.rotation.y = -a;
      g.add(p);
    }
    var c = new T.Mesh(new T.SphereGeometry(0.007, 6, 5), k.mat(0xe7d9a8));
    c.position.y = 0.006;
    g.add(c);
    g.scale.setScalar(s);
    return g;
  }
  // Place something that faces +y onto the side of the cake, facing out and tilted slightly up.
  // offset = distance along the surface from the front (viewer's right is positive).
  function onCake(obj, offset, h, out, tilt, t0, dur) {
    var tr = tierAt(h), a = around(offset / tr.r);
    var holder = k.group();
    obj.rotation.z = -Math.PI / 2 + (tilt == null ? 0.3 : tilt);
    holder.add(obj);
    holder.rotation.y = -a;
    holder.position.set(Math.cos(a) * (tr.r + out), h, Math.sin(a) * (tr.r + out));
    k.pop(holder, t0, dur || 0.45);
    return holder;
  }

  /* ---------- The cascade ---------- */
  // Path from the top of the fourth tier (viewer's right) curving down to the bottom tier (left).
  var topY = tiers[3].y1 - 0.05, botY = tiers[0].y0 + 0.28;
  function path(s) {
    return { y: topY + (botY - topY) * s, off: 0.3 - 0.78 * s + Math.sin(s * Math.PI * 2) * 0.08 };
  }
  k.cap(8.3, 'Dark green leaves');
  for (var l = 0; l < 64; l++) {
    var s = l / 63, p = path(s), edge = (l % 2 ? 1 : -1) * (0.04 + rand(l + 11) * 0.28);
    var lf = new T.Mesh(k.G.mint, leafMat);
    lf.scale.setScalar(1.4 + rand(l + 5) * 0.7);
    lf.rotation.y = edge > 0 ? -0.6 - rand(l) * 1.2 : 0.6 + rand(l) * 1.2 + Math.PI;
    onCake(lf, p.off + edge, p.y + (rand(l + 23) - 0.5) * 0.2, 0.03, 0.15, 8.4 + s * 1.3, 0.35);
  }

  k.cap(9.3, 'White sugar anemones and blush roses');
  var flowers = 30;
  for (var f = 0; f < flowers; f++) {
    var sf = f / (flowers - 1), pf = path(sf);
    var side = [-0.17, 0.15, 0, 0.21, -0.22, 0.08][f % 6];
    var isRose = [1, 4, 5].indexOf(f % 6) !== -1;
    var obj = isRose ? sugarRose(3.3 + rand(f) * 0.5) : anemone(2.0 + rand(f) * 0.35);
    onCake(obj, pf.off + side + (rand(f + 3) - 0.5) * 0.06, pf.y + (rand(f + 9) - 0.5) * 0.08, isRose ? 0.07 : 0.06, 0.3, 9.4 + sf * 3.0, 0.5);
  }

  k.cap(12.6, 'Tiny blossoms');
  for (var b = 0; b < 34; b++) {
    var sb = rand(b + 70), pb = path(sb);
    onCake(blossom(1.6 + rand(b) * 0.7), pb.off + (rand(b + 40) - 0.5) * 0.6, pb.y + (rand(b + 50) - 0.5) * 0.22, 0.075, 0.3, 12.7 + rand(b + 90) * 0.9, 0.3);
  }

  k.sparkle({ t: 13.8, y: 1.0, r: 1.2, n: 50 });
  k.cap(14.0, 'Ready for the reception');
  k.end(16);
});
