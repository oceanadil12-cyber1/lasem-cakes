/* Graduation cakes, modelled on La Sem's black-and-white graduation cake: two white fondant
   tiers, a band of graduate silhouettes throwing their caps round the bottom tier, a gold-framed
   cameo of a graduate on the top tier with gold pearls, a black mortarboard with a twisted rope
   tassel, a diploma with a gold bow, and a "Congratulations!" banner. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  var FRONT_T = 10.5;
  k.frame({ look: 0.35, h: 3.9, w: 3.1, elev: 0.5, spinSpeed: 0.06, spinStart: -FRONT_T * 0.06 });
  k.rimColor('#f1cf72');

  var silver = new T.MeshStandardMaterial({ color: 0xb4b8be, metalness: 0.7, roughness: 0.28 });
  silver.color.convertSRGBToLinear();
  var boardM = k.shadow(new T.Mesh(new T.CylinderGeometry(1.32, 1.32, 0.04, 96), silver));
  boardM.position.y = -0.88;
  k.spread(boardM, 0.2, 0.5);
  var by = -0.86;
  var fondant = k.mat(0xfbfaf7, { roughness: 0.42 });
  var black = k.mat(0x0d0c0f, { roughness: 0.75, envMapIntensity: 0.4 });
  var gold = k.metal(0xd6b25a, 0.25);

  /* ---------- Silhouette band: graduates celebrating, painted on figure by figure ---------- */
  var R1 = 0.95, H1 = 0.82;
  var W = 2048, HH = 512, sx = (W / HH) / ((2 * Math.PI * (R1 + 0.035)) / (H1 + 0.025));
  var figures = [];
  for (var i = 0; i < 26; i++) (function (i) {
    figures.push(function (g) {
      var x = (i + 0.5) / 26 * W, base = HH * 0.9, s = 0.55 + rand(i + 3) * 0.3;
      g.setTransform(sx * s, 0, 0, s, x, base);
      g.fillStyle = '#121114';
      g.beginPath(); g.ellipse(0, -170, 70, 120, 0, Math.PI, 0); g.lineTo(80, 0); g.lineTo(-80, 0); g.closePath(); g.fill();
      g.beginPath(); g.arc(0, -300, 34, 0, 7); g.fill();
      g.fillRect(-46, -350, 92, 14);
      g.lineWidth = 22; g.lineCap = 'round'; g.strokeStyle = '#121114';
      var up = rand(i + 7) > 0.35;
      g.beginPath(); g.moveTo(-40, -230); g.lineTo(-80, up ? -360 : -150); g.stroke();
      g.beginPath(); g.moveTo(40, -230); g.lineTo(85, up ? -380 : -160); g.stroke();
      if (rand(i + 11) > 0.6) { g.fillRect(70, -420, 80, 16); }
      if (rand(i + 13) > 0.55) { g.save(); g.translate(-120, -440 - rand(i) * 30); g.rotate(rand(i + 1)); g.fillRect(-40, -8, 80, 14); g.fillRect(-14, 6, 28, 18); g.restore(); }
    });
  })(i);
  var band = k.painter(W, HH, function (g, w, h) {
    g.setTransform(1, 0, 0, 1, 0, 0);
    g.fillStyle = '#fbfaf7'; g.fillRect(0, 0, w, h);
    g.fillStyle = '#121114'; g.fillRect(0, h * 0.84, w, h * 0.16);
  }, figures, { wrap: true });

  function tier(r, h, y, t0, sideMat) {
    var top = k.sponge({ r: r, h: h, y: y, t0: t0, height: 3.6, flavour: 'chocolate' });
    var g = new T.CylinderGeometry(r + 0.035, r + 0.035, h + 0.025, 96);
    g.translate(0, (h + 0.025) / 2, 0);
    var coat = k.shadow(new T.Mesh(g, [sideMat || fondant, fondant, fondant]));
    coat.rotation.y = Math.PI;
    coat.position.y = y;
    k.rise(coat, t0 + 0.7, 0.6);
    return top + 0.025;
  }
  k.cap(0.3, 'Chocolate sponge, two tiers');
  var top1 = tier(R1, H1, by, 0.4, new T.MeshStandardMaterial({ map: band.tex, roughness: 0.42 }));
  k.cap(1.1, 'White fondant');
  var R2 = 0.62, top2 = tier(R2, 0.76, top1, 1.7);
  k.cap(3.1, 'Graduates throwing their caps');
  k.reveal(band, 3.1, 1.8);

  k.cap(5.0, 'Gold pearls and a cameo');
  k.pearls({ r: R2 + 0.06, y: top1, n: 46, size: 0.03, t0: 5.0, step: 0.012, mat: gold });
  // Cameo: a graduate's profile in black inside an ornate gold frame, on the front of the top tier.
  var cameoTex = k.canvasTex(400, 480, function (g, w, h) {
    g.clearRect(0, 0, w, h);
    g.fillStyle = '#fbfaf7'; g.beginPath(); g.ellipse(w / 2, h / 2, 150, 190, 0, 0, 7); g.fill();
    g.fillStyle = '#121114';
    g.beginPath(); g.moveTo(150, 400); g.quadraticCurveTo(140, 330, 175, 300); g.quadraticCurveTo(150, 270, 160, 220);
    g.quadraticCurveTo(170, 160, 230, 160); g.quadraticCurveTo(275, 170, 270, 230); g.lineTo(285, 255); g.lineTo(268, 262); g.quadraticCurveTo(272, 300, 240, 300);
    g.quadraticCurveTo(250, 340, 290, 360); g.lineTo(300, 400); g.closePath(); g.fill();
    g.beginPath(); g.moveTo(120, 150); g.lineTo(240, 120); g.lineTo(330, 150); g.lineTo(220, 178); g.closePath(); g.fill();
    g.fillRect(185, 150, 70, 30);
    g.strokeStyle = '#c9a24a'; g.lineWidth = 16; g.beginPath(); g.ellipse(w / 2, h / 2, 160, 200, 0, 0, 7); g.stroke();
    g.fillStyle = '#d8b25a';
    for (var i = 0; i < 26; i++) { var a = i / 26 * Math.PI * 2; g.beginPath(); g.arc(w / 2 + Math.cos(a) * 172, h / 2 + Math.sin(a) * 212, 13, 0, 7); g.fill(); }
  });
  var cameo = new T.Mesh(new T.PlaneGeometry(0.54, 0.65), new T.MeshStandardMaterial({ map: cameoTex, transparent: true, alphaTest: 0.3, roughness: 0.4, metalness: 0.2 }));
  cameo.position.set(0, top1 + 0.4, R2 + 0.045);
  k.pop(cameo, 5.4, 0.5);

  // Congratulations banner wrapped round the front of the bottom tier.
  k.cap(6.2, 'Congratulations!');
  var bannerOps = [];
  for (var s = 1; s <= 20; s++) (function (s) {
    bannerOps.push(function (g, w, h) {
      g.save(); g.beginPath(); g.rect(0, 0, w * s / 20, h); g.clip();
      g.fillStyle = '#b8913e'; g.textAlign = 'center'; g.textBaseline = 'middle';
      g.font = '120px "Kaushan Script", cursive';
      g.fillText('Congratulations!', w / 2, h / 2 + 8);
      g.restore();
    });
  })(s);
  var bannerPaint = k.painter(1024, 200, function (g, w, h) { g.fillStyle = '#fbfaf7'; g.fillRect(0, 0, w, h); }, bannerOps);
  var arc = 1.7, bg = new T.CylinderGeometry(R1 + 0.1, R1 + 0.13, 0.2, 48, 1, true, Math.PI - arc / 2, arc);
  var banner = k.shadow(new T.Mesh(bg, new T.MeshStandardMaterial({ map: bannerPaint.tex, roughness: 0.45, side: T.DoubleSide })));
  banner.rotation.y = Math.PI;
  banner.position.y = by + 0.12;
  k.pop(banner, 6.2, 0.5);
  k.reveal(bannerPaint, 6.5, 1.3);

  // Diploma with a gold bow.
  k.cap(7.8, 'A diploma with a gold bow');
  var dip = k.group();
  var paper = k.shadow(new T.Mesh(new T.CylinderGeometry(0.07, 0.07, 0.5, 24), k.mat(0xfbf8f0, { roughness: 0.5 })));
  paper.rotation.z = Math.PI / 2;
  var curl = new T.Mesh(new T.TorusGeometry(0.05, 0.02, 8, 20), k.mat(0xfbf8f0));
  curl.rotation.y = Math.PI / 2;
  curl.position.x = 0.25;
  var tie = new T.Mesh(new T.TorusGeometry(0.073, 0.015, 8, 24), gold);
  tie.rotation.y = Math.PI / 2;
  [-1, 1].forEach(function (sd) { var l = new T.Mesh(new T.TorusGeometry(0.05, 0.012, 6, 16), gold); l.position.set(0.02, 0.05 + 0.02, sd * 0.05); l.rotation.set(0, Math.PI / 2, sd * 0.6); dip.add(l); });
  dip.add(paper, curl, tie);
  dip.rotation.y = -0.15;
  k.drop(dip, 7.9, 0.6, [-0.12, top2 + 0.07, 0.36], { spin: 0.6, height: 1.8 });

  // Mortarboard with a twisted black rope tassel hanging over the side.
  k.cap(8.6, 'Caps off to the graduate');
  var cap = k.group();
  var boardTop = k.shadow(new T.Mesh(new T.BoxGeometry(1.05, 0.05, 1.05), black));
  boardTop.position.y = 0.36;
  boardTop.rotation.y = Math.PI / 4 + 0.15;
  var skull = k.shadow(new T.Mesh(new T.CylinderGeometry(0.34, 0.36, 0.34, 40), black));
  skull.position.y = 0.17;
  var btn = new T.Mesh(new T.CylinderGeometry(0.04, 0.04, 0.03, 16), black);
  btn.position.y = 0.4;
  var path = new T.CatmullRomCurve3([k.v(0, 0.41, 0), k.v(0.25, 0.42, 0.1), k.v(0.55, 0.4, 0.2), k.v(0.66, 0.2, 0.24), k.v(0.68, -0.05, 0.25)]);
  var rope1 = new T.Mesh(new T.TubeGeometry(path, 60, 0.018, 6, false), black);
  var twisted = [];
  for (var t = 0; t <= 1.0001; t += 0.02) { var p = path.getPoint(t); twisted.push(p.add(k.v(Math.cos(t * 60) * 0.016, Math.sin(t * 60) * 0.016, Math.sin(t * 60) * 0.016))); }
  var rope2 = new T.Mesh(new T.TubeGeometry(new T.CatmullRomCurve3(twisted), 120, 0.016, 6, false), black);
  var tassel = new T.Mesh(new T.CylinderGeometry(0.02, 0.06, 0.2, 12), black);
  tassel.position.set(0.68, -0.15, 0.25);
  cap.add(boardTop, skull, btn, rope1, rope2, tassel);
  cap.scale.setScalar(1.18);
  cap.traverse(function (c) { c.castShadow = true; });
  k.drop(cap, 8.7, 0.8, [0.05, top2 - 0.02, -0.08], { spin: 1.2, height: 2.4, squash: 0.06 });
  k.confetti({ t: 9.6, n: 150, colors: ['#f1cf72', '#ffffff', '#121114', '#d6b25a'] });
  k.cap(10.0, 'Congratulations, graduate');
  k.end(12);
});
