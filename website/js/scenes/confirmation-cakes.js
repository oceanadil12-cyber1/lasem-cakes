/* Confirmation cakes, modelled on La Sem's cross-shaped cream tart: two layers of golden puff
   pastry filled with piped cream, topped with caramel rosettes, grey and white macarons,
   white chocolate strawberries, white sugar flowers, gold and silver pearls, and a
   "God Bless" plaque. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  var FRONT_T = 11;
  // Turned a little extra so the stem points at the viewer once the camera framing is taken into account.
  k.frame({ look: -0.55, h: 2.2, w: 3.0, elev: 0.75, spinSpeed: 0.04, spinStart: -FRONT_T * 0.04 - 0.16 });
  k.rimColor('#efb08a');

  /* ---------- Embossed silver board ---------- */
  var emboss = k.canvasTex(512, 512, function (g, w, h) {
    g.fillStyle = '#808080'; g.fillRect(0, 0, w, h);
    g.strokeStyle = 'rgba(255,255,255,.45)'; g.lineWidth = 3;
    for (var i = 0; i < 40; i++) { var x = rand(i) * w, y = rand(i + 1) * h; for (var p = 0; p < 6; p++) { var a = p * Math.PI / 3; g.beginPath(); g.ellipse(x + Math.cos(a) * 14, y + Math.sin(a) * 14, 14, 5, a, 0, 7); g.stroke(); } }
  }, false);
  var silver = new T.MeshStandardMaterial({ color: 0xb9bdc3, metalness: 0.75, roughness: 0.3, bumpMap: emboss, bumpScale: 0.004 });
  silver.color.convertSRGBToLinear();
  var by = k.board({ w: 3.3, d: 3.1, h: 0.04, mat: silver });

  /* ---------- Cross outline ---------- */
  var BW = 0.36, L = 1.3, ARM = 1.08, ZC = -0.52; // half widths, stem half-length, arm half-length, arm centre
  function inCross(x, z) { return (Math.abs(x) <= BW && Math.abs(z) <= L) || (Math.abs(z - ZC) <= BW && Math.abs(x) <= ARM); }
  var cs = new T.Shape();
  [[-BW, -L], [BW, -L], [BW, ZC - BW], [ARM, ZC - BW], [ARM, ZC + BW], [BW, ZC + BW], [BW, L], [-BW, L], [-BW, ZC + BW], [-ARM, ZC + BW], [-ARM, ZC - BW], [-BW, ZC - BW]]
    .forEach(function (p, i) { if (i) cs.lineTo(p[0], -p[1]); else cs.moveTo(p[0], -p[1]); });
  function crossSlab(h) {
    var g = new T.ExtrudeGeometry(cs, { depth: h, bevelEnabled: true, bevelThickness: 0.008, bevelSize: 0.01, bevelSegments: 1 });
    g.rotateX(-Math.PI / 2);
    return g;
  }
  var flaky = k.canvasTex(512, 512, function (g, w, h) {
    g.fillStyle = '#d9a35a'; g.fillRect(0, 0, w, h);
    for (var i = 0; i < 500; i++) { g.fillStyle = rand(i) > 0.5 ? 'rgba(150,90,30,.35)' : 'rgba(255,225,160,.45)'; g.fillRect(rand(i + 1) * w, rand(i + 2) * h, 6 + rand(i + 3) * 18, 2 + rand(i + 4) * 3); }
  });
  var layers = k.canvasTex(256, 64, function (g, w, h) {
    for (var y = 0; y < h; y += 4) { g.fillStyle = (y / 4) % 2 ? '#c88a40' : '#ebc27e'; g.fillRect(0, y, w, 4); }
  });
  var pastryMats = [new T.MeshStandardMaterial({ map: flaky, roughness: 0.7 }), new T.MeshStandardMaterial({ map: layers, roughness: 0.75 })];

  // Smooth piped cream kiss.
  var kissProf = [];
  for (var i = 0; i <= 12; i++) { var q = i / 12; kissProf.push(new T.Vector2(Math.max(0.072 * Math.pow(Math.cos(q * Math.PI / 2), 0.7), 0.002), q * 0.1)); }
  var kissGeo = new T.LatheGeometry(kissProf, 24);
  var cream = k.mat(0xfefcf8, { roughness: 0.45 });

  function grid(spacing, border) {
    var pts = [];
    for (var x = -ARM + spacing / 2; x <= ARM; x += spacing) for (var z = -L + spacing / 2; z <= L; z += spacing) {
      if (!inCross(x, z)) continue;
      var isEdge = !inCross(x + spacing, z) || !inCross(x - spacing, z) || !inCross(x, z + spacing) || !inCross(x, z - spacing);
      if (border === undefined || border === isEdge) pts.push([x, z]);
    }
    return pts;
  }
  function creamLayer(y, t0, dur) {
    grid(0.145).forEach(function (p, i, all) {
      var m = new T.Mesh(kissGeo, cream);
      m.receiveShadow = true;
      m.position.set(p[0], y, p[1]);
      k.pipe(m, t0 + (i / all.length) * dur, 0.3);
    });
  }

  /* ---------- Layers ---------- */
  k.cap(0.3, 'Golden puff pastry');
  var p1 = k.shadow(new T.Mesh(crossSlab(0.06), pastryMats));
  k.drop(p1, 0.4, 0.8, [0, by, 0], { squash: 0.06, height: 2.6 });
  k.burst({ t: 0.9, y: by, r: 1.2, color: 0xf3d9a8 });
  var y1 = by + 0.075;
  k.cap(1.3, 'Piped vanilla cream');
  creamLayer(y1, 1.3, 1.6);
  var y2 = y1 + 0.095;
  k.cap(3.1, 'A second layer of pastry');
  var p2 = k.shadow(new T.Mesh(crossSlab(0.06), pastryMats));
  k.drop(p2, 3.1, 0.7, [0, y2, 0], { squash: 0.06, height: 2.4 });
  var y3 = y2 + 0.075;
  k.cap(3.9, 'Cream on top');
  creamLayer(y3, 3.9, 1.4);
  var topY = y3 + 0.07;

  /* ---------- Decorations on top ---------- */
  var caramel = k.mat(0xb8915a, { roughness: 0.5 });
  var caramelGeo = k.G.rosetteGeo(0.09, 0.09, 7);
  var macGrey = new T.MeshStandardMaterial({ color: 0xa4a9b0, metalness: 0.55, roughness: 0.4 });
  macGrey.color.convertSRGBToLinear();
  var macWhite = k.mat(0xf5f2ea, { roughness: 0.55 });
  var white = k.mat(0xfcfbf7, { roughness: 0.5 });
  var gold = k.metal(0xe0bd62, 0.2), silverBead = k.metal(0xd8dce2, 0.15);

  function macaron(mat) {
    var g = k.group(), shell = new T.SphereGeometry(0.1, 24, 12);
    [0.075, 0.025].forEach(function (yy, j) {
      var s = k.shadow(new T.Mesh(shell, mat));
      s.scale.set(1, 0.4, 1);
      s.position.y = yy;
      if (j) s.rotation.x = Math.PI;
      g.add(s);
    });
    var fill = new T.Mesh(new T.CylinderGeometry(0.085, 0.085, 0.025, 24), k.mat(0xf6efe2));
    fill.position.y = 0.05;
    g.add(fill);
    g.rotation.set(-1.2, 0, 0);
    var h = k.group(); h.add(g);
    return h;
  }
  function strawberryWhite() {
    var m = k.shadow(new T.Mesh(k.G.strawberry, white));
    m.rotation.x = Math.PI / 2 + 0.2;
    var h = k.group(); h.add(m);
    m.position.y = 0.08;
    return h;
  }
  function daisy() {
    var g = k.group(), pg = new T.SphereGeometry(0.03, 10, 8);
    for (var i = 0; i < 6; i++) { var a = i * Math.PI / 3, p = new T.Mesh(pg, white); p.scale.set(1.3, 0.4, 0.8); p.position.set(Math.cos(a) * 0.04, 0.02, Math.sin(a) * 0.04); p.rotation.y = -a; g.add(p); }
    var c = new T.Mesh(new T.SphereGeometry(0.018, 10, 8), white);
    c.position.y = 0.035;
    g.add(c);
    g.scale.setScalar(1.4);
    return g;
  }

  // Lay decorations in a pattern down the stem and across the arms.
  k.cap(5.4, 'Caramel rosettes');
  var spots = grid(0.24);
  spots.sort(function (a, b) { return (a[1] - b[1]) || (a[0] - b[0]); });
  // A repeating mix like the photo: caramel rosettes, macarons, white strawberries, flowers and small white rosettes.
  var kinds = ['caramel', 'macGrey', 'strawberry', 'white', 'caramel', 'macWhite', 'rose', 'caramel', 'daisy', 'macGrey', 'white', 'strawberry'];
  spots.forEach(function (p, i) {
    var kind = kinds[(i + Math.floor(i / 3) * 5) % kinds.length], obj, t0;
    var x = p[0] + (rand(i) - 0.5) * 0.05, z = p[1] + (rand(i + 3) - 0.5) * 0.05;
    if (kind === 'caramel') { obj = k.shadow(new T.Mesh(caramelGeo, caramel)); t0 = 5.5 + rand(i + 9) * 0.8; k.pipe(obj, t0, 0.4); obj.position.set(x, topY, z); return; }
    if (kind === 'macGrey') { obj = macaron(macGrey); t0 = 6.4; }
    else if (kind === 'macWhite') { obj = macaron(macWhite); t0 = 6.4; }
    else if (kind === 'strawberry') { obj = strawberryWhite(); t0 = 7.2; }
    else if (kind === 'rose') { obj = k.rose({ color: 0xfbfaf6, scale: 1.0 }); t0 = 7.7; }
    else if (kind === 'daisy') { obj = daisy(); t0 = 7.7; }
    else { obj = k.shadow(new T.Mesh(k.G.rosetteGeo(0.06, 0.08, 8), white)); obj.position.set(x, topY, z); k.pipe(obj, 7.9 + rand(i) * 0.4, 0.35); return; }
    obj.rotation.y = rand(i + 5) * 6;
    k.drop(obj, t0 + rand(i + 7) * 0.7, 0.55, [x, topY, z], { spin: 1, height: 1.8 });
  });
  k.cap(6.4, 'Grey and white macarons');
  k.cap(7.2, 'White chocolate strawberries');
  k.cap(7.7, 'White sugar flowers');

  k.cap(8.5, 'Gold and silver pearls');
  // A random spot on the cross (each retry uses a new seed, so this always finishes).
  function spotOnCross(seed) {
    for (var tries = 0; tries < 50; tries++) {
      var x = (rand(seed * 101 + tries * 2) - 0.5) * 2 * ARM, z = (rand(seed * 101 + tries * 2 + 1) - 0.5) * 2 * L;
      if (inCross(x, z)) return [x, z];
    }
    return [0, 0];
  }
  var bead = new T.SphereGeometry(0.018, 10, 8);
  for (var d = 0; d < 44; d++) {
    var sp = spotOnCross(d + 1), bx = sp[0], bz = sp[1];
    k.drop(new T.Mesh(bead, d % 3 ? gold : silverBead), 8.6 + rand(d) * 0.6, 0.45, [bx, topY + 0.07, bz], { spin: 4, height: 1.6 });
  }
  var leafMat = k.gloss(0x2f5a2a, { roughness: 0.4, side: T.DoubleSide });
  for (var l = 0; l < 10; l++) {
    var lp = spotOnCross(l + 200), lx = lp[0], lz = lp[1];
    var lf = new T.Mesh(k.G.mint, leafMat);
    lf.scale.setScalar(0.7);
    lf.rotation.set(-Math.PI / 2 + 0.6, 0, rand(l) * 6);
    k.drop(lf, 8.8 + l * 0.05, 0.6, [lx, topY + 0.08, lz], { flutter: true });
  }

  // Cloud-shaped plaque at the crossing.
  k.cap(9.4, 'God bless');
  var pl = k.plaque({ text: 'God Bless', sub: 'ON YOUR CONFIRMATION', w: 0.62, h: 0.36, bg: '#fffdf8', ink: '#b8913e', subInk: '#b8913e' });
  pl.rotation.x = -0.35;
  var ph = k.group(); ph.add(pl);
  pl.position.y = 0.2;
  k.drop(ph, 9.5, 0.7, [0, topY + 0.05, ZC + 0.1], { spin: 0.3, height: 2 });
  k.sparkle({ t: 10.2, y: topY, r: 1.1, n: 40, color: 0xfff1cf });
  k.cap(10.5, 'For a meaningful celebration');
  k.end(12);
});
