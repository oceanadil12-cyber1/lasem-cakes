/* Baby shower cakes, modelled on La Sem's "Goodbye Tummy, Hello Mummy" sheet cake: a white
   sheet cake with pink shell borders and a satin ribbon, a sugar baby peeking out from under a
   pink blanket, pink booties, a ribbon bow, pink blossoms, and the message piped in pink. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  var FRONT_T = 10;
  k.frame({ look: -0.5, h: 1.75, w: 2.85, elev: 1.0, spinSpeed: 0.05, spinStart: -FRONT_T * 0.05 });
  k.rimColor('#f4b3c6');

  var by = k.board({ w: 3.0, d: 2.05, h: 0.05, mat: k.mat(0xf3f1ec, { roughness: 0.6 }) });
  var W = 2.5, D = 1.6, H = 0.42;
  var pink = k.gloss(0xf2a3c0, { roughness: 0.4, clearcoat: 0.3 });
  var pinkSatin = k.gloss(0xeb93b4, { roughness: 0.3, clearcoat: 0.6 });
  var icing = k.mat(0xfdfbf8, { roughness: 0.5 });

  /* ---------- The sheet cake ---------- */
  k.cap(0.2, 'A vanilla sheet cake');
  var spongeGeo = new T.BoxGeometry(W - 0.06, H - 0.04, D - 0.06);
  spongeGeo.translate(0, (H - 0.04) / 2, 0);
  var spongeMesh = k.shadow(new T.Mesh(spongeGeo, k.mat(0xeccd8a, { roughness: 0.9 })));
  k.drop(spongeMesh, 0.3, 0.9, [0, by, 0], { squash: 0.08, height: 3 });
  k.burst({ t: 0.86, y: by, r: 1.3 });
  k.cap(1.3, 'Smooth white buttercream');
  var coatGeo = new T.BoxGeometry(W, H, D);
  coatGeo.translate(0, H / 2, 0);
  var coat = k.shadow(new T.Mesh(coatGeo, icing));
  coat.position.y = by;
  k.rise(coat, 1.3, 0.8);
  var top = by + H;

  // Pink shell border along the top edge, and a beaded border at the base.
  k.cap(2.2, 'Pink shell borders');
  var shellGeo = k.G.rosetteGeo(0.045, 0.05, 6);
  var edge = [];
  var nx = 30, nz = 19;
  for (var i = 0; i < nx; i++) { var x = -W / 2 + (i + 0.5) * W / nx; edge.push([x, D / 2 - 0.035], [x, -D / 2 + 0.035]); }
  for (var j = 0; j < nz; j++) { var z = -D / 2 + (j + 0.5) * D / nz; edge.push([W / 2 - 0.035, z], [-W / 2 + 0.035, z]); }
  edge.forEach(function (e, n) {
    var s = k.shadow(new T.Mesh(shellGeo, pink));
    s.position.set(e[0], top, e[1]);
    k.pipe(s, 2.3 + (n % 60) * 0.012 + Math.floor(n / 60) * 0.1, 0.3);
  });
  var beadGeo = new T.SphereGeometry(0.022, 8, 6);
  for (var b = 0; b < 70; b++) {
    var t = b / 70, px, pz;
    if (t < 0.5) { px = -W / 2 + t * 2 * W; pz = D / 2 + 0.01; } else { px = -W / 2 - 0.01; pz = -D / 2 + (t - 0.5) * 2 * D; }
    var bd = new T.Mesh(beadGeo, pink);
    bd.position.set(px, by + 0.025, pz);
    k.pop(bd, 2.6 + b * 0.01, 0.25);
  }

  // Satin ribbon round the sides with a bow at the front.
  k.cap(3.2, 'A satin ribbon and bow');
  var band = k.group();
  [[W + 0.02, 0.08, 0.012, 0, D / 2 + 0.006], [W + 0.02, 0.08, 0.012, 0, -D / 2 - 0.006], [0.012, 0.08, D + 0.02, W / 2 + 0.006, 0], [0.012, 0.08, D + 0.02, -W / 2 - 0.006, 0]].forEach(function (p) {
    var m = new T.Mesh(new T.BoxGeometry(p[0], p[1], p[2]), pinkSatin);
    m.position.set(p[3], 0.04, p[4]);
    band.add(m);
  });
  band.position.y = by + 0.12;
  k.rise(band, 3.2, 0.4);
  var bow = k.group();
  [-1, 1].forEach(function (s) {
    var loop = new T.Mesh(new T.TorusGeometry(0.1, 0.022, 10, 28), pinkSatin);
    loop.scale.set(1.35, 0.75, 0.4);
    loop.position.x = s * 0.13;
    loop.rotation.z = s * 0.2;
    bow.add(loop);
    var tail = new T.Mesh(new T.BoxGeometry(0.06, 0.2, 0.01), pinkSatin);
    tail.position.set(s * 0.06, -0.13, 0.01);
    tail.rotation.z = s * 0.35;
    bow.add(tail);
  });
  bow.add(new T.Mesh(new T.SphereGeometry(0.035, 12, 10), pinkSatin));
  bow.position.set(0, by + 0.16, D / 2 + 0.03);
  k.pop(bow, 3.6, 0.45);

  /* ---------- Piped message ---------- */
  k.cap(4.1, 'Goodbye Tummy, Hello Mummy');
  var steps = 24, msgOps = [];
  for (var m = 1; m <= steps; m++) (function (m) {
    msgOps.push(function (g, w, h) {
      g.save();
      g.beginPath(); g.rect(0, 0, w * m / steps, h); g.clip();
      g.clearRect(0, 0, w, h);
      g.fillStyle = '#d9558a'; g.textBaseline = 'middle';
      g.font = '104px "Kaushan Script", cursive';
      g.fillText('Goodbye Tummy', 30, h * 0.3);
      g.fillText('Hello Mummy', 30, h * 0.72);
      g.restore();
    });
  })(m);
  var msg = k.painter(1024, 340, function (g, w, h) { g.clearRect(0, 0, w, h); }, msgOps);
  var msgPlane = new T.Mesh(new T.PlaneGeometry(1.5, 0.5), new T.MeshStandardMaterial({ map: msg.tex, transparent: true, roughness: 0.5, depthWrite: false }));
  msgPlane.rotation.x = -Math.PI / 2;
  msgPlane.position.set(-0.4, top + 0.003, 0.38);
  k.add(msgPlane);
  k.reveal(msg, 4.1, 1.6);

  /* ---------- The baby under the blanket ---------- */
  k.cap(5.9, 'A sugar baby under a pink blanket');
  var skin = k.mat(0xf1c3a3, { roughness: 0.55 });
  var face = k.canvasTex(256, 256, function (g, w, h) {
    g.fillStyle = '#f1c3a3'; g.fillRect(0, 0, w, h);
    g.fillStyle = '#3c6fb5';
    [[96, 120], [160, 120]].forEach(function (e) { g.beginPath(); g.ellipse(e[0], e[1], 13, 16, 0, 0, 7); g.fill(); g.fillStyle = '#fff'; g.beginPath(); g.arc(e[0] + 4, e[1] - 5, 4, 0, 7); g.fill(); g.fillStyle = '#3c6fb5'; });
    g.fillStyle = 'rgba(235,120,130,.45)'; g.beginPath(); g.arc(78, 160, 18, 0, 7); g.fill(); g.beginPath(); g.arc(178, 160, 18, 0, 7); g.fill();
    g.strokeStyle = '#c96a6a'; g.lineWidth = 5; g.beginPath(); g.arc(128, 168, 16, 0.2, Math.PI - 0.2); g.stroke();
  });
  var baby = k.group();
  var head = k.shadow(new T.Mesh(new T.SphereGeometry(0.16, 28, 20), new T.MeshStandardMaterial({ map: face, roughness: 0.55 })));
  head.rotation.y = -Math.PI / 2;
  head.position.set(0, 0.16, 0);
  baby.add(head);
  [-1, 1].forEach(function (s) {
    var arm = k.shadow(new T.Mesh(new T.CylinderGeometry(0.05, 0.055, 0.26, 12), skin));
    arm.rotation.set(Math.PI / 2 - 0.2, 0, s * 0.35);
    arm.position.set(s * 0.17, 0.06, 0.12);
    var hand = k.shadow(new T.Mesh(new T.SphereGeometry(0.058, 12, 10), skin));
    hand.position.set(s * 0.22, 0.04, 0.24);
    baby.add(arm, hand);
  });
  var babyAt = [-0.62, -0.2];
  baby.position.set(babyAt[0], top, babyAt[1] + 0.05);
  k.pop(baby, 6.0, 0.5);

  // Blanket: a cloth that falls and settles over the baby, with a soft sparkle print.
  var lace = k.canvasTex(512, 512, function (g, w, h) {
    g.fillStyle = '#f6bcd0'; g.fillRect(0, 0, w, h);
    for (var i = 0; i < 260; i++) { g.fillStyle = 'rgba(255,255,255,' + (0.3 + rand(i) * 0.5) + ')'; g.beginPath(); g.arc(rand(i + 1) * w, rand(i + 2) * h, 1.5 + rand(i + 3) * 2, 0, 7); g.fill(); }
    g.strokeStyle = 'rgba(255,255,255,.55)'; g.lineWidth = 3;
    for (var j = 0; j < 14; j++) { var x = rand(j + 50) * w, y = rand(j + 60) * h; for (var p = 0; p < 5; p++) { var a = p * 1.256; g.beginPath(); g.ellipse(x + Math.cos(a) * 10, y + Math.sin(a) * 10, 9, 5, a, 0, 7); g.stroke(); } }
  });
  var cloth = new T.PlaneGeometry(1.25, 0.95, 40, 30);
  cloth.rotateX(-Math.PI / 2);
  (function () {
    var p = cloth.attributes.position;
    for (var i = 0; i < p.count; i++) {
      var x = p.getX(i), z = p.getZ(i);
      // A hood over the baby's head at the front edge, folds everywhere else.
      var dx = x + 0.3, dz = z - 0.38, d = Math.sqrt(dx * dx + dz * dz * 1.6);
      var lift = 0.36 * Math.exp(-d * d * 8);
      var folds = 0.02 * Math.sin(x * 9 + z * 4) + 0.015 * Math.sin(z * 13);
      p.setY(i, lift + folds + 0.02);
    }
    cloth.computeVertexNormals();
  })();
  var blanket = k.shadow(new T.Mesh(cloth, new T.MeshStandardMaterial({ map: lace, roughness: 0.75, side: T.DoubleSide })));
  blanket.rotation.y = 0;
  k.drop(blanket, 6.5, 0.9, [babyAt[0] + 0.3, top, babyAt[1] - 0.47], { height: 1.4, flutter: true, spin: 0.2 });

  /* ---------- Booties, bow and blossoms ---------- */
  k.cap(7.6, 'Pink booties');
  function bootie() {
    var g = k.group();
    var sole = k.shadow(new T.Mesh(new T.SphereGeometry(0.1, 20, 14), pink));
    sole.scale.set(0.75, 0.42, 1.3);
    sole.position.set(0, 0.04, 0.02);
    var upper = k.shadow(new T.Mesh(new T.CylinderGeometry(0.072, 0.082, 0.13, 20), pink));
    upper.position.set(0, 0.11, -0.05);
    var cuff = new T.Mesh(new T.TorusGeometry(0.073, 0.016, 8, 20), k.mat(0xf8cfdc));
    cuff.rotation.x = Math.PI / 2;
    cuff.position.set(0, 0.18, -0.05);
    var lace1 = new T.Mesh(new T.TorusGeometry(0.03, 0.007, 6, 14), k.mat(0xffffff));
    lace1.position.set(0, 0.1, 0.05);
    g.add(sole, upper, cuff, lace1);
    return g;
  }
  [[0.55, -0.42, 0.25], [0.78, -0.3, -0.25]].forEach(function (p, i) {
    var bt = bootie();
    bt.scale.setScalar(1.5);
    bt.rotation.y = p[2] + Math.PI;
    k.drop(bt, 7.7 + i * 0.25, 0.6, [p[0], top, p[1]], { spin: 0.6 });
  });

  k.cap(8.4, 'A ribbon bow');
  var sheer = new T.MeshPhysicalMaterial({ color: 0xd8467a, roughness: 0.3, transparent: true, opacity: 0.75, side: T.DoubleSide, clearcoat: 0.5 });
  sheer.color.convertSRGBToLinear();
  var topBow = k.group();
  [-1, 1].forEach(function (s) {
    var l = new T.Mesh(new T.TorusGeometry(0.12, 0.012, 8, 30), sheer);
    l.scale.set(1.3, 0.8, 0.5);
    l.position.x = s * 0.15;
    l.rotation.set(0.4, 0, s * 0.3);
    topBow.add(l);
  });
  var rope = new T.Mesh(new T.TorusGeometry(0.2, 0.018, 8, 40, Math.PI * 1.2), k.mat(0xffffff, { roughness: 0.6 }));
  rope.rotation.x = Math.PI / 2;
  rope.position.set(0.05, 0.01, 0.1);
  topBow.add(rope);
  topBow.position.set(0.72, top + 0.05, 0.1);
  k.pop(topBow, 8.5, 0.5);

  k.cap(9.0, 'Pink blossoms');
  var petal = new T.SphereGeometry(0.03, 10, 8);
  var blossomPink = k.mat(0xf3a1bf, { roughness: 0.5 });
  [[0.0, 0.55], [0.15, 0.6], [-0.05, 0.68], [0.95, 0.35], [1.05, 0.5], [0.4, 0.45], [-1.05, 0.6], [1.0, -0.6], [0.2, -0.55], [-0.2, 0.15]].forEach(function (p, i) {
    var f = k.group();
    for (var q = 0; q < 5; q++) {
      var a = q * Math.PI * 0.4, pt = new T.Mesh(petal, blossomPink);
      pt.scale.set(1, 0.35, 0.7);
      pt.position.set(Math.cos(a) * 0.032, 0.01, Math.sin(a) * 0.032);
      pt.rotation.y = -a;
      f.add(pt);
    }
    var ctr = new T.Mesh(new T.SphereGeometry(0.013, 8, 6), k.mat(0xffffff));
    ctr.position.y = 0.02;
    f.add(ctr);
    f.scale.setScalar(1.3 + rand(i) * 0.5);
    k.drop(f, 9.1 + i * 0.08, 0.5, [p[0], top + 0.005, p[1]], { spin: 2, flutter: true });
  });
  k.confetti({ t: 9.8, n: 120, colors: ['#f6bcd0', '#f3a1bf', '#ffffff', '#fbe3ea'] });
  k.cap(10.0, 'Welcome, little one');
  k.end(11.5);
});
