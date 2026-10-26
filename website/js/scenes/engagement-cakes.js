/* Engagement cakes, modelled on La Sem's "wedding gown" cake: a tall white buttercream cake on
   a silver board, a skirt of pink buttercream ruffles piped from the waist down the front and
   spreading out over the board, a pink bow at the waist, pink hearts and a bride-and-groom
   topper. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  // Turntable starts turned so the gown faces the viewer as the build finishes (t = 11).
  k.frame({ look: 0.0, h: 3.5, w: 3.5, elev: 0.55, spinSpeed: 0.06, spinStart: -11 * 0.06 });
  k.rimColor('#f0a3b0');

  /* ---------- Silver board ---------- */
  var emboss = k.canvasTex(512, 512, function (g, w, h) {
    g.fillStyle = '#9a9ea6'; g.fillRect(0, 0, w, h);
    g.strokeStyle = 'rgba(255,255,255,.35)'; g.lineWidth = 2;
    for (var i = 0; i < 60; i++) {
      var x = rand(i) * w, y = rand(i + 1) * h, r = 10 + rand(i + 2) * 22;
      for (var p = 0; p < 6; p++) { var a = p * Math.PI / 3; g.beginPath(); g.ellipse(x + Math.cos(a) * r * 0.6, y + Math.sin(a) * r * 0.6, r * 0.5, r * 0.18, a, 0, 7); g.stroke(); }
    }
  }, false);
  var silver = new T.MeshStandardMaterial({ color: 0xa7abb2, metalness: 0.7, roughness: 0.3, bumpMap: emboss, bumpScale: 0.004 });
  silver.color.convertSRGBToLinear();
  var board = k.shadow(new T.Mesh(new T.CylinderGeometry(1.75, 1.75, 0.04, 96), silver));
  board.position.y = -0.88;
  k.spread(board, 0.2, 0.5);
  var by = -0.86;

  /* ---------- The cake ---------- */
  var R = 0.92, buttercream = k.mat(0xfbfaf8, { roughness: 0.5 });
  k.cap(0.6, 'Vanilla sponge');
  var y = k.sponge({ y: by, r: R, h: 0.5, t0: 0.7 });
  k.cap(1.4, 'Strawberry cream');
  y = k.cream({ y: y, r: R, h: 0.06, t0: 1.45, mat: k.mat(0xf6c3cf) });
  y = k.sponge({ y: y, r: R, h: 0.5, t0: 1.95 });
  k.cap(2.8, 'Smooth white buttercream');
  k.coat({ r: R + 0.035, y0: by, y1: y + 0.03, t0: 2.8, mat: buttercream });
  var top = y + 0.03, Rc = R + 0.035;
  k.pearls({ r: Rc + 0.012, y: by, n: 60, size: 0.024, t0: 3.6, step: 0.008 });

  // Pink hearts on the sides.
  var heart = new T.Shape();
  heart.moveTo(0, -0.05);
  heart.bezierCurveTo(0.075, 0.01, 0.05, 0.07, 0, 0.038);
  heart.bezierCurveTo(-0.05, 0.07, -0.075, 0.01, 0, -0.05);
  var hg = new T.ExtrudeGeometry(heart, { depth: 0.012, bevelEnabled: true, bevelSize: 0.006, bevelThickness: 0.006, bevelSegments: 2 });
  hg.center();
  var pink = k.gloss(0xf08cb4, { roughness: 0.35, clearcoat: 0.4 });
  [[1.15, 0.65], [1.45, 0.25], [-1.2, 0.55], [-1.5, 0.2], [2.3, 0.45], [-2.4, 0.5]].forEach(function (h, i) {
    var a = Math.PI / 2 - h[0], m = k.shadow(new T.Mesh(hg, pink));
    var holder = k.group(); holder.add(m);
    holder.rotation.y = Math.PI / 2 - a;
    holder.position.set(Math.cos(a) * (Rc + 0.008), by + h[1] * (top - by), Math.sin(a) * (Rc + 0.008));
    k.pop(holder, 3.9 + i * 0.1, 0.35);
  });

  /* ---------- The gown: ruffles piped from the waist down and out over the board ---------- */
  // Centre line, width and outward direction of the skirt at v (0 = waist, 1 = hem).
  function skirt(v, u) {
    var y, zc, w, out;
    if (v < 0.72) {
      var q = v / 0.72;
      y = top - 0.04 - q * (top - by - 0.16);
      w = 0.16 + q * 0.58;
      out = 0.035 + q * 0.12;
      var x = u * w, rr = Rc + out;
      return new T.Vector3(x, y, Math.sqrt(Math.max(rr * rr - x * x, 0.01)));
    }
    var q2 = (v - 0.72) / 0.28;
    y = by + 0.12 - q2 * 0.09;
    w = 0.74 + q2 * 0.22;
    zc = Rc + 0.155 + q2 * 0.42;
    var x2 = u * w;
    return new T.Vector3(x2, y + (1 - u * u) * 0.04, zc - (u * u) * 0.25 * (1 - q2 * 0.4));
  }
  // A smooth pink underskirt so no cake shows between the ruffles.
  var U = 24, V = 30, pos = [], idx = [];
  for (var j = 0; j <= V; j++) for (var i = 0; i <= U; i++) { var p = skirt(j / V, i / U * 2 - 1); pos.push(p.x, p.y - 0.01, p.z - 0.02); }
  for (var j2 = 0; j2 < V; j2++) for (var i2 = 0; i2 < U; i2++) { var a0 = j2 * (U + 1) + i2, a1 = a0 + U + 1; idx.push(a0, a1, a0 + 1, a1, a1 + 1, a0 + 1); }
  var ug = new T.BufferGeometry();
  ug.setAttribute('position', new T.Float32BufferAttribute(pos, 3));
  ug.setIndex(idx);
  ug.computeVertexNormals();
  var frosting = k.gloss(0xf07fb2, { roughness: 0.45, clearcoat: 0.3, side: T.DoubleSide });
  var under = k.shadow(new T.Mesh(ug, frosting));
  k.anim(under, 4.6, 2.6, function (q) { ug.setDrawRange(0, Math.floor(idx.length * k.easeOut(q) / 6) * 6); });

  // One ruffle: a squat piped dollop whose edge folds up and down in soft waves, twisting toward the top.
  var prof = [];
  for (var r = 0; r <= 16; r++) { var t = r / 16; prof.push(new T.Vector2(Math.max(0.085 * Math.sin((1 - t) * Math.PI * 0.5 + 0.12), 0.003), Math.sin(t * Math.PI * 0.5) * 0.075)); }
  var ruffleGeo = new T.LatheGeometry(prof, 64);
  (function () {
    var pp = ruffleGeo.attributes.position;
    for (var i = 0; i < pp.count; i++) {
      var x = pp.getX(i), yy = pp.getY(i), z = pp.getZ(i), th = Math.atan2(z, x), h = yy / 0.075;
      var wave = Math.sin(th * 5 + h * 4);
      var f = 1 + 0.2 * wave * (1 - h * 0.5);
      pp.setXYZ(i, x * f, yy + wave * 0.022 * (1 - h), z * f);
    }
    ruffleGeo.computeVertexNormals();
  })();
  k.cap(4.6, 'A gown of pink buttercream ruffles');
  var rows = 15, n = 0, normal = new T.Vector3(), up = new T.Vector3(0, 1, 0);
  for (var row = 0; row < rows; row++) {
    var v = (row + 0.5) / rows, per = Math.round(2.5 + v * 9);
    for (var c = 0; c < per; c++, n++) {
      var u = per === 1 ? 0 : (c / (per - 1)) * 2 - 1;
      u *= 0.92;
      var pt = skirt(Math.min(v + (rand(n) - 0.5) * 0.03, 1), u + (rand(n + 5) - 0.5) * 0.06);
      var m = new T.Mesh(ruffleGeo, frosting);
      m.castShadow = true;
      var s = 1.05 + rand(n + 9) * 0.35 + v * 0.25;
      m.scale.set(s, s * 1.2, s);
      // Face outward from the cake (and up on the board), with a random twist.
      if (v < 0.72) normal.set(pt.x, 0.15, pt.z).normalize();
      else normal.set(pt.x * 0.4, 1.4, pt.z * 0.35).normalize();
      m.quaternion.setFromUnitVectors(up, normal);
      m.rotateY(rand(n + 13) * 6);
      m.position.copy(pt);
      k.pipe(m, 4.7 + v * 2.4 + rand(n + 17) * 0.15, 0.35);
    }
  }
  k.sparkle({ t: 7.2, y: by + 0.2, r: 0.9, n: 40, color: 0xffd6e6 });

  // Bow at the waist.
  k.cap(7.4, 'A sugar bow at the waist');
  var bow = k.group();
  [-1, 1].forEach(function (sx) {
    var lobe = k.shadow(new T.Mesh(new T.SphereGeometry(0.1, 20, 14), frosting));
    lobe.scale.set(1.3, 0.62, 0.45);
    lobe.position.x = sx * 0.12;
    lobe.rotation.z = sx * 0.2;
    bow.add(lobe);
  });
  var knot = k.shadow(new T.Mesh(new T.SphereGeometry(0.05, 16, 12), frosting));
  knot.scale.set(1, 1, 0.7);
  bow.add(knot);
  bow.scale.setScalar(1.35);
  bow.position.set(0, top + 0.05, Rc + 0.07);
  bow.rotation.x = -0.2;
  k.pop(bow, 7.5, 0.5);

  // Bride-and-groom topper: a cut-out drawn on a card, standing behind the bow.
  k.cap(8.3, 'The happy couple on top');
  var art = k.canvasTex(512, 640, function (g, w, h) {
    g.clearRect(0, 0, w, h);
    // Groom: dark suit.
    g.fillStyle = '#20232c';
    g.beginPath(); g.moveTo(250, 640); g.lineTo(255, 300); g.quadraticCurveTo(300, 250, 370, 270); g.quadraticCurveTo(420, 300, 420, 400); g.lineTo(430, 640); g.closePath(); g.fill();
    g.fillStyle = '#f5f5f5'; g.beginPath(); g.moveTo(300, 285); g.lineTo(330, 280); g.lineTo(318, 380); g.closePath(); g.fill();
    g.fillStyle = '#e1b49a'; g.beginPath(); g.ellipse(330, 200, 44, 56, -0.2, 0, 7); g.fill();
    g.fillStyle = '#3b2a20'; g.beginPath(); g.ellipse(335, 160, 48, 30, -0.2, Math.PI, 7); g.fill();
    g.fillStyle = '#4a3226'; g.beginPath(); g.ellipse(318, 232, 30, 18, -0.2, 0, Math.PI); g.fill();
    // Bride: white gown, veil, brown hair.
    g.fillStyle = 'rgba(255,255,255,.75)'; g.beginPath(); g.moveTo(240, 140); g.quadraticCurveTo(150, 300, 130, 640); g.lineTo(250, 640); g.closePath(); g.fill();
    g.fillStyle = '#fbfaf7';
    g.beginPath(); g.moveTo(180, 640); g.lineTo(205, 330); g.quadraticCurveTo(245, 280, 290, 320); g.lineTo(300, 640); g.closePath(); g.fill();
    g.fillStyle = '#e8bfa5'; g.beginPath(); g.ellipse(255, 300, 38, 52, 0.1, 0, 7); g.fill();
    g.fillStyle = '#6a4630'; g.beginPath(); g.ellipse(258, 205, 46, 58, 0.25, 0, 7); g.fill();
    g.fillStyle = '#e8bfa5'; g.beginPath(); g.ellipse(290, 200, 22, 34, 0.4, 0, 7); g.fill();
    g.strokeStyle = '#e8bfa5'; g.lineWidth = 22; g.lineCap = 'round';
    g.beginPath(); g.moveTo(285, 330); g.quadraticCurveTo(330, 340, 360, 300); g.stroke();
  });
  var card = new T.Mesh(new T.PlaneGeometry(0.84, 1.05), new T.MeshStandardMaterial({ map: art, transparent: true, alphaTest: 0.4, side: T.DoubleSide, roughness: 0.6 }));
  card.castShadow = true;
  card.position.y = 0.5;
  var stand = k.group(); stand.add(card);
  k.drop(stand, 8.4, 0.7, [0.06, top - 0.02, Rc - 0.24], { height: 2.2 });

  k.sparkle({ t: 9.2, y: top, r: 0.7, n: 36, color: 0xffe0ec });
  k.cap(9.6, 'Here comes the bride');
  k.end(12);
});
