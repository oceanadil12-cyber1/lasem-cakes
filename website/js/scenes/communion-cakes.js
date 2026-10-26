/* Communion cakes: an open-book cake. The book block lands, its pages open with
   "First Communion" written inside, pearls trim the cover, and a gold chalice and grapes
   are set beside it. */
LaSem3D.start(function (k) {
  var T = k.T;
  // The turntable starts turned so the open book faces the viewer squarely at 9 s.
  k.frame({ look: -0.35, h: 2.5, w: 3.2, elev: 1.05, spinStart: -9 * 0.16 });
  k.rimColor('#ead39b');
  var by = k.board({ w: 2.9, d: 2.1, mat: k.metal(0xd9dde3, 0.3) });
  var W = 2.1, D = 1.45;

  // Book block with page edges on the sides.
  var edges = k.canvasTex(512, 64, function (g, w, h) {
    g.fillStyle = '#fbf6ec'; g.fillRect(0, 0, w, h);
    g.strokeStyle = 'rgba(160,140,110,.35)';
    for (var y = 4; y < h; y += 5) { g.beginPath(); g.moveTo(0, y); g.lineTo(w, y); g.stroke(); }
  });
  var cover = k.mat(0xf6efe3, { roughness: 0.45 });
  var sideMat = new T.MeshStandardMaterial({ map: edges, roughness: 0.6 });
  k.cap(0.2, 'Vanilla sponge, cut to a book');
  var blockGeo = new T.BoxGeometry(W, 0.34, D);
  blockGeo.translate(0, 0.17, 0);
  var block = k.shadow(new T.Mesh(blockGeo, [sideMat, sideMat, cover, cover, sideMat, sideMat]));
  k.drop(block, 0.3, 0.9, [0, by, 0], { squash: 0.08, height: 3 });
  k.burst({ t: 0.86, y: by, r: 1.2 });

  k.cap(1.4, 'White fondant cover');
  var coverGeo = new T.BoxGeometry(W + 0.1, 0.05, D + 0.1);
  coverGeo.translate(0, 0.025, 0);
  var cv = k.shadow(new T.Mesh(coverGeo, k.mat(0xffffff, { roughness: 0.35 })));
  cv.position.y = by - 0.02;
  k.spread(cv, 1.4, 0.5);

  // Two pages that open from closed (standing up) to lying flat, slightly curved.
  k.cap(2.2, 'The pages open');
  function pageTex(lines, cross) {
    return k.canvasTex(512, 360, function (g, w, h) {
      g.fillStyle = '#fffdf8'; g.fillRect(0, 0, w, h);
      g.strokeStyle = '#d9bf7f'; g.lineWidth = 6; g.strokeRect(20, 20, w - 40, h - 40);
      g.fillStyle = '#9a7a33'; g.textAlign = 'center'; g.textBaseline = 'middle';
      if (cross) {
        g.fillRect(w / 2 - 9, 70, 18, 150); g.fillRect(w / 2 - 50, 110, 100, 18);
        g.font = '600 34px "Fraunces", Georgia, serif'; g.fillText(lines, w / 2, 280);
      } else {
        g.font = '74px "Kaushan Script", cursive';
        lines.split('|').forEach(function (l, i) { g.fillText(l, w / 2, 130 + i * 100); });
      }
    });
  }
  var pages = [[-1, 'God bless', true], [1, 'First|Communion', false]];
  pages.forEach(function (p, i) {
    var geo = new T.BoxGeometry(W / 2 - 0.04, 0.05, D - 0.06);
    geo.translate(p[0] * (W / 4 - 0.02), 0.025, 0);
    var face = new T.MeshStandardMaterial({ map: pageTex(p[1], p[2]), roughness: 0.5 });
    var page = k.shadow(new T.Mesh(geo, [sideMat, sideMat, face, cover, sideMat, sideMat]));
    page.position.y = by + 0.34;
    // Hinged at the spine: starts standing up, opens flat with a slight valley in the middle.
    k.anim(page, 2.3 + i * 0.25, 0.9, function (q) {
      page.rotation.z = p[0] * ((1 - k.easeOut(q)) * 1.45 + 0.06);
    });
    // Redraw the writing once the fonts have loaded.
    if (document.fonts) Promise.all([document.fonts.load('74px "Kaushan Script"'), document.fonts.load('600 34px "Fraunces"')]).then(function () {
      face.map = pageTex(p[1], p[2]);
      face.needsUpdate = true;
    });
  });

  k.cap(3.8, 'Pearls along the cover');
  var pearl = k.gloss(0xfff8ee, { roughness: 0.25 });
  var pg = new T.SphereGeometry(0.028, 12, 10), n = 0;
  [[-W / 2 - 0.03, 1], [W / 2 + 0.03, 1]].forEach(function (s) {
    for (var j = 0; j < 16; j++) {
      var m = k.shadow(new T.Mesh(pg, pearl));
      m.position.set(s[0], by + 0.03, -D / 2 + (j + 0.5) * (D / 16));
      k.pop(m, 3.9 + (n++) * 0.02, 0.3);
    }
  });
  [[-D / 2 - 0.03], [D / 2 + 0.03]].forEach(function (s) {
    for (var j = 0; j < 22; j++) {
      var m = k.shadow(new T.Mesh(pg, pearl));
      m.position.set(-W / 2 + (j + 0.5) * (W / 22), by + 0.03, s[0]);
      k.pop(m, 3.9 + (n++) * 0.02, 0.3);
    }
  });

  k.cap(5.4, 'A gold chalice');
  var prof = [[0, 0], [0.16, 0], [0.17, 0.02], [0.06, 0.06], [0.035, 0.12], [0.06, 0.2], [0.035, 0.26], [0.04, 0.3], [0.13, 0.36], [0.17, 0.52], [0.16, 0.53], [0.115, 0.38], [0, 0.36]]
    .map(function (q) { return new T.Vector2(q[0], q[1]); });
  var chalice = k.shadow(new T.Mesh(new T.LatheGeometry(prof, 48), k.M.gold));
  k.drop(chalice, 5.5, 0.8, [1.2, by - 0.02, 0.55], { spin: 0.8 });

  k.cap(6.4, 'Grapes');
  var grape = k.gloss(0x5a2a5e, { roughness: 0.3 }), gg = new T.SphereGeometry(0.045, 14, 10);
  var bunch = k.group();
  for (var gi = 0; gi < 14; gi++) {
    var row = Math.floor(Math.sqrt(gi * 2)), ga = gi * 2.4;
    var gm = k.shadow(new T.Mesh(gg, grape));
    gm.position.set(Math.cos(ga) * 0.04 * (4 - row) * 0.6, 0.05 * (5 - row), Math.sin(ga) * 0.04 * (4 - row) * 0.6);
    bunch.add(gm);
  }
  bunch.rotation.z = Math.PI / 2.4;
  k.drop(bunch, 6.5, 0.7, [-1.22, by + 0.06, 0.5], { spin: 1 });
  k.sparkle({ t: 7.3, y: by + 0.2, r: 1.2, n: 46 });
  k.cap(7.6, 'A milestone to remember');
  k.end(9.6);
});
