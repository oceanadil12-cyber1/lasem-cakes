/* Birthday & novelty cakes, modelled on La Sem's black-and-gold birthday cake: two black
   shimmer buttercream tiers on a wide black board, brushed with gold strokes, gold sugar
   ruffles, a gold chocolate champagne bottle and a gold "Happy Birthday" topper. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  var FRONT_T = 10.5;
  k.frame({ look: 0.4, h: 4.2, w: 3.3, elev: 0.5, spinSpeed: 0.06, spinStart: -FRONT_T * 0.06 });
  k.rimColor('#f2d27c');
  var gold = k.metal(0xd9b45c, 0.25);

  /* ---------- Black board with a gold edge ---------- */
  var grooves = k.canvasTex(512, 512, function (g, w, h) {
    g.fillStyle = '#808080'; g.fillRect(0, 0, w, h);
    g.strokeStyle = 'rgba(0,0,0,.4)'; g.lineWidth = 2;
    for (var i = 0; i < 180; i++) { var a = i / 180 * Math.PI * 2; g.beginPath(); g.moveTo(w / 2 + Math.cos(a) * 120, h / 2 + Math.sin(a) * 120); g.lineTo(w / 2 + Math.cos(a) * 256, h / 2 + Math.sin(a) * 256); g.stroke(); }
  }, false);
  var boardMat = new T.MeshStandardMaterial({ color: 0x141316, roughness: 0.95, envMapIntensity: 0.15, bumpMap: grooves, bumpScale: 0.01 });
  boardMat.color.convertSRGBToLinear();
  var board = k.shadow(new T.Mesh(new T.CylinderGeometry(1.55, 1.55, 0.06, 96), [k.mat(0x1a191c), boardMat, boardMat]));
  board.position.y = -0.87;
  var edge = new T.Mesh(new T.CylinderGeometry(1.5, 1.5, 0.05, 96, 1, true), gold);
  edge.position.y = -0.875;
  k.spread(board, 0.2, 0.5);
  k.spread(edge, 0.2, 0.5);
  var by = -0.84;

  /* ---------- Black shimmer tiers brushed with gold ---------- */
  function brushed(r, h, seed) {
    var W = 1024, H = 256, ops = [], mops = [];
    for (var i = 0; i < 420; i++) (function (i) {
      // Fine dry-brush strokes rising from a solid gold base, with ragged tops.
      var x = rand(seed + i) * W, top = H * (0.5 + rand(seed + i + 1) * 0.32 - (i % 9 === 0 ? 0.12 : 0)), wdt = 1 + rand(seed + i + 2) * 3.2;
      function stroke(g, color) {
        g.strokeStyle = color; g.lineWidth = wdt; g.lineCap = 'round';
        g.beginPath(); g.moveTo(x, H); g.quadraticCurveTo(x + (rand(seed + i + 4) - 0.5) * 8, (H + top) / 2, x + (rand(seed + i + 3) - 0.5) * 10, top); g.stroke();
        if (i % 40 === 0) { g.fillStyle = color; g.fillRect(0, H * 0.9, W, H * 0.1); }
      }
      ops.push(function (g) { stroke(g, ['#d9b45c', '#b8913e', '#efd38a'][i % 3]); });
      mops.push(function (g) { stroke(g, '#ffffff'); });
    })(i);
    var color = k.painter(W, H, function (g, w, hh) {
      g.fillStyle = '#121114'; g.fillRect(0, 0, w, hh);
      for (var j = 0; j < 900; j++) { g.fillStyle = 'rgba(255,255,255,' + (0.05 + rand(seed + j) * 0.2) + ')'; g.fillRect(rand(seed + j + 1) * w, rand(seed + j + 2) * hh, 1.4, 1.4); }
    }, ops, { wrap: true });
    var metal = k.painter(W, H, function (g, w, hh) { g.fillStyle = '#000'; g.fillRect(0, 0, w, hh); }, mops, { wrap: true, linear: true });
    var mat = new T.MeshStandardMaterial({ map: color.tex, metalnessMap: metal.tex, metalness: 1, roughness: 0.45, envMapIntensity: 0.8 });
    return { mat: mat, color: color, metal: metal };
  }
  var blackTop = k.mat(0x111013, { roughness: 0.95, envMapIntensity: 0.15 });
  function tier(r, h, y, t0, look) {
    var top = k.sponge({ r: r, h: h, y: y, t0: t0, height: 3.8, flavour: 'chocolate' });
    var g = new T.CylinderGeometry(r + 0.035, r + 0.035, h + 0.025, 96);
    g.translate(0, (h + 0.025) / 2, 0);
    var coat = k.shadow(new T.Mesh(g, [look.mat, blackTop, blackTop]));
    coat.position.y = y;
    k.rise(coat, t0 + 0.7, 0.6);
    return top + 0.025;
  }
  var b1 = brushed(0.95, 0.8, 10), b2 = brushed(0.58, 0.78, 90);
  k.cap(0.4, 'Dark chocolate sponge');
  var top1 = tier(0.95, 0.8, by, 0.5, b1);
  k.cap(1.2, 'Black shimmer buttercream');
  var top2 = tier(0.58, 0.78, top1, 1.8, b2);
  k.cap(3.3, 'Brushed with gold');
  [b1, b2].forEach(function (b, i) { k.reveal(b.color, 3.3 + i * 0.3, 1.6); k.reveal(b.metal, 3.3 + i * 0.3, 1.6); });

  /* ---------- Gold sugar ruffles ---------- */
  function ruffle(seed, s) {
    var g = new T.PlaneGeometry(0.42, 0.34, 24, 18), p = g.attributes.position;
    for (var i = 0; i < p.count; i++) {
      var x = p.getX(i), y = p.getY(i), d = Math.hypot(x, y + 0.17);
      p.setZ(i, Math.sin(Math.atan2(y + 0.17, x) * 7 + seed) * 0.05 * d * 3 + x * x * 0.8);
      if (d > 0.21) { var f = 0.21 / d; p.setX(i, x * f); p.setY(i, (y + 0.17) * f - 0.17); }
    }
    g.computeVertexNormals();
    var m = new T.Mesh(g, new T.MeshStandardMaterial({ color: 0xd9b45c, metalness: 1, roughness: 0.32, side: T.DoubleSide }));
    m.material.color.convertSRGBToLinear();
    m.castShadow = true;
    m.scale.setScalar(s);
    return m;
  }
  k.cap(5.2, 'Gold sugar ruffles');
  // [angle from front (+ = viewer's right), height, radius, scale, tilt]
  [[0.75, top1 + 0.12, 0.62, 1.6, -0.4], [1.05, top1 + 0.3, 0.6, 1.3, -0.2], [0.55, top1 + 0.45, 0.6, 1.1, 0.1],
   [-1.05, by + 0.35, 0.99, 1.5, 0.3], [-1.3, by + 0.58, 0.97, 1.2, 0.5], [-0.85, by + 0.12, 1.0, 1.3, 0.1],
   [0.25, top2 + 0.05, 0.25, 1.4, 0.0], [-0.3, top2 + 0.05, 0.22, 1.2, 0.2]].forEach(function (r, i) {
    var a = Math.PI / 2 - r[0], m = ruffle(i * 1.7, r[3] * 1.4);
    var h = k.group(); h.add(m);
    m.rotation.set(r[4], 0, (rand(i) - 0.5) * 0.6);
    h.rotation.y = Math.PI / 2 - a;
    h.position.set(Math.cos(a) * r[2], r[1], Math.sin(a) * r[2]);
    k.pop(h, 5.3 + i * 0.12, 0.45);
  });

  /* ---------- Gold champagne bottle leaning on the top tier ---------- */
  k.cap(6.4, 'A gold chocolate champagne bottle');
  var prof = [[0, 0], [0.11, 0], [0.115, 0.02], [0.115, 0.36], [0.1, 0.43], [0.06, 0.52], [0.04, 0.6], [0.04, 0.74], [0.045, 0.76], [0, 0.76]].map(function (q) { return new T.Vector2(q[0], q[1]); });
  var bottle = k.shadow(new T.Mesh(new T.LatheGeometry(prof, 40), gold));
  var bh = k.group(); bh.add(bottle);
  bottle.rotation.z = 0.42;
  var ba = Math.PI / 2 - 0.35;
  bh.position.set(Math.cos(ba) * 0.78, top1, Math.sin(ba) * 0.78);
  bh.rotation.y = -0.35;
  k.drop(bh, 6.5, 0.7, [bh.position.x, top1, bh.position.z], { height: 2.2, spin: 0.3 });

  /* ---------- Gold "Happy Birthday" topper ---------- */
  k.cap(7.4, 'Happy birthday!');
  var words = k.canvasTex(800, 520, function (g, w, h) {
    g.clearRect(0, 0, w, h);
    g.fillStyle = '#fff'; g.textAlign = 'center'; g.textBaseline = 'middle';
    g.font = '190px "Kaushan Script", cursive';
    g.fillText('Happy', w / 2, 150);
    g.fillText('Birthday', w / 2, 370);
  }, false);
  var topperMat = new T.MeshStandardMaterial({ color: 0xf0cf7a, metalness: 0.6, roughness: 0.25, emissive: 0x3a2a08, alphaMap: words, transparent: true, alphaTest: 0.4, side: T.DoubleSide });
  topperMat.color.convertSRGBToLinear();
  if (document.fonts) document.fonts.load('190px "Kaushan Script"').then(function () {
    var c = words.image.getContext('2d');
    c.clearRect(0, 0, 800, 520); c.fillStyle = '#fff'; c.textAlign = 'center'; c.textBaseline = 'middle';
    c.font = '190px "Kaushan Script", cursive'; c.fillText('Happy', 400, 150); c.fillText('Birthday', 400, 370);
    words.needsUpdate = true;
  });
  var topper = new T.Mesh(new T.PlaneGeometry(1.25, 0.82), topperMat);
  topper.castShadow = true;
  topper.position.y = 0.45;
  var stick = new T.Mesh(new T.CylinderGeometry(0.008, 0.008, 0.3, 6), gold);
  stick.position.y = 0.1;
  var th = k.group(); th.add(topper, stick);
  k.drop(th, 7.5, 0.7, [0, top2 - 0.02, 0.05], { height: 2.2 });
  k.sparkle({ t: 8.2, y: top2 + 0.2, r: 0.9, n: 44 });
  k.confetti({ t: 8.6, n: 140, colors: ['#d9b45c', '#efd38a', '#ffffff', '#121114'] });
  k.cap(8.8, 'Make a wish');
  k.end(11);
});
