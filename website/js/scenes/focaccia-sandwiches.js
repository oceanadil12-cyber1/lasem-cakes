/* Focaccia sandwiches: "The Zesty Southern Italian" is built layer by layer on homemade
   focaccia: pesto, mortadella, hot salami, mozzarella, sweet peppers, caramelized onions,
   arugula, and the top, pinned with an olive. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  k.frame({ look: -0.55, h: 1.6, w: 2.6, elev: 0.6 });
  k.rimColor('#d4dd8f');
  var by = k.board({ w: 2.8, d: 1.9 });

  var bread = k.canvasTex(512, 512, function (g, w, h) {
    var grd = g.createRadialGradient(w / 2, h / 2, 40, w / 2, h / 2, w * 0.7);
    grd.addColorStop(0, '#d99a45'); grd.addColorStop(1, '#a8621f');
    g.fillStyle = grd; g.fillRect(0, 0, w, h);
    for (var i = 0; i < 70; i++) { var x = rand(i) * w, y = rand(i + 1) * h; g.fillStyle = 'rgba(150,90,30,.45)'; g.beginPath(); g.ellipse(x, y, 11, 8, 0, 0, 7); g.fill(); g.fillStyle = 'rgba(255,225,160,.4)'; g.beginPath(); g.ellipse(x - 2, y - 3, 6, 4, 0, 0, 7); g.fill(); }
    for (var j = 0; j < 90; j++) { g.save(); g.translate(rand(j + 9) * w, rand(j + 10) * h); g.rotate(rand(j + 11) * 6); g.fillStyle = '#3e5a26'; g.fillRect(-6, -1, 12, 2.5); g.restore(); }
    for (var s = 0; s < 120; s++) { g.fillStyle = 'rgba(255,255,255,.8)'; g.fillRect(rand(s + 50) * w, rand(s + 51) * h, 2.5, 2.5); }
  });
  var crumb = k.mat(0xe9cf98, { roughness: 0.9 });
  var crustSide = k.mat(0xb8732c, { roughness: 0.8 });
  var crust = new T.MeshStandardMaterial({ map: bread, roughness: 0.75 });
  var W = 1.3, D = 0.95;
  function slab(h) {
    var geo = new T.ExtrudeGeometry(k.G.roundedRect(W, D, 0.22), { depth: h, bevelEnabled: true, bevelThickness: 0.04, bevelSize: 0.04, bevelSegments: 4 });
    geo.rotateX(-Math.PI / 2);
    var uv = geo.attributes.uv, p = geo.attributes.position;
    for (var i = 0; i < uv.count; i++) uv.setXY(i, (p.getX(i) + W / 2) / W, (p.getZ(i) + D / 2) / D);
    return geo;
  }
  var fa = k.front(9);
  var stack = k.group();
  stack.rotation.y = k.facing(9);
  k.add(stack);
  function put(obj, t0, y, o) {
    stack.add(obj);
    k.drop(obj, t0, (o && o.dur) || 0.55, [obj.position.x, y, obj.position.z], Object.assign({ height: 2.2 }, o || {}));
  }

  k.cap(0.2, 'Homemade focaccia');
  var bottom = k.shadow(new T.Mesh(slab(0.13), [crumb, crustSide]));
  put(bottom, 0.3, by, { squash: 0.08, dur: 0.7 });
  var y = by + 0.17;

  k.cap(1.1, 'Pesto');
  var pesto = k.shadow(new T.Mesh(new T.CylinderGeometry(0.5, 0.5, 0.012, 40), k.gloss(0x5a7d2a, { roughness: 0.5, clearcoat: 0.3 })));
  pesto.scale.set(1.15, 1, 0.8);
  pesto.position.y = y;
  stack.add(pesto);
  k.spread(pesto, 1.1, 0.4);
  y += 0.014;

  k.cap(1.6, 'Mortadella');
  var mort = k.canvasTex(256, 256, function (g, w, h) {
    g.fillStyle = '#efa7a4'; g.fillRect(0, 0, w, h);
    for (var i = 0; i < 26; i++) { g.fillStyle = 'rgba(255,245,240,.9)'; g.beginPath(); g.arc(rand(i) * w, rand(i + 1) * h, 4 + rand(i + 2) * 6, 0, 7); g.fill(); }
  });
  var mortMat = new T.MeshStandardMaterial({ map: mort, roughness: 0.5 });
  function wavy(r, mat, amp) {
    var geo = new T.CylinderGeometry(r, r, 0.012, 48, 1), pos = geo.attributes.position;
    for (var i = 0; i < pos.count; i++) { var x = pos.getX(i), z = pos.getZ(i); pos.setY(i, pos.getY(i) + Math.sin(Math.atan2(z, x) * 5) * amp * Math.hypot(x, z) / r); }
    geo.computeVertexNormals();
    return k.shadow(new T.Mesh(geo, mat));
  }
  [[-0.25, 0.05], [0.22, -0.05], [0, 0.12]].forEach(function (p, i) {
    var m = wavy(0.36, mortMat, 0.03);
    m.position.set(p[0], 0, p[1]);
    m.rotation.y = i;
    put(m, 1.6 + i * 0.2, y + 0.03 + i * 0.012);
  });
  y += 0.08;

  k.cap(2.4, 'Hot salami');
  var sal = k.canvasTex(256, 256, function (g, w, h) {
    g.fillStyle = '#9e2a25'; g.fillRect(0, 0, w, h);
    for (var i = 0; i < 70; i++) { g.fillStyle = 'rgba(250,220,210,.85)'; g.beginPath(); g.arc(rand(i) * w, rand(i + 1) * h, 2 + rand(i + 2) * 4, 0, 7); g.fill(); }
  });
  var salMat = new T.MeshStandardMaterial({ map: sal, roughness: 0.45 });
  [[-0.35, -0.2], [0.35, 0.2], [0.3, -0.25], [-0.3, 0.22]].forEach(function (p, i) {
    var m = k.shadow(new T.Mesh(new T.CylinderGeometry(0.17, 0.17, 0.012, 32), salMat));
    m.position.set(p[0], 0, p[1]);
    m.rotation.set((rand(i) - 0.5) * 0.15, 0, (rand(i + 1) - 0.5) * 0.15);
    put(m, 2.4 + i * 0.12, y);
  });
  y += 0.02;

  k.cap(3.1, 'Mozzarella');
  [[-0.25, 0], [0.25, 0.02]].forEach(function (p, i) {
    var m = k.shadow(new T.Mesh(new T.CylinderGeometry(0.27, 0.27, 0.03, 32), k.mat(0xfbf8ef, { roughness: 0.6 })));
    m.position.set(p[0], 0, p[1]);
    m.scale.set(1.1, 1, 0.9);
    put(m, 3.2 + i * 0.2, y + 0.015);
  });
  y += 0.035;

  k.cap(3.8, 'Sweet peppers');
  [0xd8352a, 0xf2b632, 0xd8352a, 0xf2b632, 0xd8352a].forEach(function (c, i) {
    var strip = k.shadow(new T.Mesh(new T.TorusGeometry(0.2, 0.025, 8, 20, Math.PI * 0.8), k.gloss(c, { roughness: 0.25 })));
    strip.rotation.set(Math.PI / 2, 0, i * 1.3);
    strip.position.set((i - 2) * 0.16, 0, (rand(i) - 0.5) * 0.2);
    strip.scale.set(1, 1, 0.5);
    put(strip, 3.9 + i * 0.09, y + 0.01);
  });
  k.cap(4.5, 'Caramelized onions');
  for (var o = 0; o < 7; o++) {
    var on = k.shadow(new T.Mesh(new T.TorusGeometry(0.07, 0.012, 6, 16, Math.PI), k.gloss(0xa8642a, { roughness: 0.3 })));
    on.rotation.set(Math.PI / 2, 0, rand(o) * 6);
    on.position.set((rand(o + 1) - 0.5) * 0.9, 0, (rand(o + 2) - 0.5) * 0.6);
    put(on, 4.6 + o * 0.05, y + 0.02);
  }
  y += 0.03;

  k.cap(5.1, 'Fresh arugula');
  for (var a = 0; a < 16; a++) {
    var lf = k.leaf('#3f7a2a', 1.5);
    lf.rotation.set(-Math.PI / 2 + (rand(a) - 0.5) * 0.6, 0, rand(a + 1) * 6);
    lf.position.set((rand(a + 2) - 0.5) * 1.0, 0, (rand(a + 3) - 0.5) * 0.7);
    put(lf, 5.2 + a * 0.04, y + 0.02 + rand(a + 4) * 0.02, { flutter: true, dur: 0.8 });
  }
  y += 0.05;

  k.cap(6.2, 'The top goes on');
  var topSlab = k.shadow(new T.Mesh(slab(0.11), [crust, crustSide]));
  put(topSlab, 6.3, y, { squash: 0.1, dur: 0.7 });
  var topY = y + 0.15;

  k.cap(7.2, 'Pinned with an olive');
  var pick = k.group();
  var stick = new T.Mesh(new T.CylinderGeometry(0.008, 0.008, 0.42, 6), k.mat(0xe7d3a4));
  stick.position.y = 0.06;
  var olive = k.shadow(new T.Mesh(new T.SphereGeometry(0.05, 16, 12), k.gloss(0x6d7a2a, { roughness: 0.2 })));
  olive.scale.set(1, 1.25, 1);
  olive.position.y = 0.25;
  pick.add(stick, olive);
  put(pick, 7.3, topY - 0.05, { dur: 0.6 });
  k.cap(8.0, 'The Zesty Southern Italian');
  k.end(9.5);
});
