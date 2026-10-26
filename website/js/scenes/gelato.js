/* Gelato: a waffle cone is set in its stand and scoops of pistachio, stracciatella and
   strawberry stack on top; a glass cup gets bacio and lemon with a wafer. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  k.frame({ look: 0.05, h: 3.1, w: 3.0, elev: 0.95 });
  k.rimColor('#a6e3cf');
  // Marble counter slab.
  var marble = k.canvasTex(512, 512, function (g, w, h) {
    g.fillStyle = '#f2efea'; g.fillRect(0, 0, w, h);
    g.strokeStyle = 'rgba(140,140,150,.35)';
    for (var i = 0; i < 14; i++) {
      g.lineWidth = 1 + rand(i) * 3;
      g.beginPath(); var y = rand(i + 1) * h; g.moveTo(0, y);
      for (var x = 0; x <= w; x += 24) g.lineTo(x, y + Math.sin(x / 50 + i * 2) * 20 + (rand(i + x) - 0.5) * 8);
      g.stroke();
    }
  });
  var by = k.board({ w: 2.9, d: 1.8, h: 0.1, mat: new T.MeshStandardMaterial({ map: marble, roughness: 0.25, metalness: 0 }) });

  // Rough gelato scoop with a frilly lip at the bottom.
  function scoopGeo(seed) {
    var geo = new T.SphereGeometry(0.2, 40, 28), pos = geo.attributes.position, v = new T.Vector3();
    for (var i = 0; i < pos.count; i++) {
      v.fromBufferAttribute(pos, i);
      var n = 1 + 0.06 * Math.sin(v.x * 30 + seed) * Math.cos(v.z * 26 + seed * 2) + 0.04 * Math.sin(v.y * 40 + seed);
      if (v.y < -0.06) n *= 1 + 0.12 * Math.max(0, Math.sin(Math.atan2(v.z, v.x) * 9 + seed));
      v.multiplyScalar(n);
      v.y *= 0.85;
      pos.setXYZ(i, v.x, v.y, v.z);
    }
    geo.computeVertexNormals();
    return geo;
  }
  function flavour(color, specks) {
    if (!specks) return k.mat(color, { roughness: 0.6 });
    var tex = k.canvasTex(256, 256, function (g, w, h) {
      g.fillStyle = '#' + color.toString(16).padStart(6, '0'); g.fillRect(0, 0, w, h);
      for (var i = 0; i < 160; i++) { g.fillStyle = specks; g.beginPath(); g.ellipse(rand(i) * w, rand(i + 1) * h, 2 + rand(i + 2) * 5, 1.5, rand(i + 3) * 3, 0, 7); g.fill(); }
    });
    return new T.MeshStandardMaterial({ map: tex, roughness: 0.6 });
  }
  var fa = k.front(9), side = fa - Math.PI / 2;
  function at(x, z) { return [Math.cos(side) * x + Math.cos(fa) * z, Math.sin(side) * x + Math.sin(fa) * z]; }

  // Cone stand and waffle cone.
  var steel = k.metal(0xcfd3d8, 0.25);
  var cp = at(-0.42, 0);
  var holder = k.group();
  var ringM = k.shadow(new T.Mesh(new T.TorusGeometry(0.13, 0.012, 8, 32), steel));
  ringM.rotation.x = Math.PI / 2;
  ringM.position.y = 0.42;
  holder.add(ringM);
  for (var l = 0; l < 3; l++) {
    var leg = k.shadow(new T.Mesh(new T.CylinderGeometry(0.01, 0.012, 0.44, 6), steel));
    var la = l * Math.PI * 2 / 3;
    leg.position.set(Math.cos(la) * 0.15, 0.21, Math.sin(la) * 0.15);
    leg.rotation.set(Math.sin(la) * 0.1, 0, -Math.cos(la) * 0.1);
    holder.add(leg);
  }
  var baseRing = new T.Mesh(new T.TorusGeometry(0.17, 0.012, 8, 32), steel);
  baseRing.rotation.x = Math.PI / 2;
  baseRing.position.y = 0.01;
  holder.add(baseRing);
  k.cap(0.2, 'A crisp waffle cone');
  k.drop(holder, 0.3, 0.6, [cp[0], by, cp[1]], { height: 1.8 });
  var waffle = k.canvasTex(256, 256, function (g, w, h) {
    g.fillStyle = '#d9a35a'; g.fillRect(0, 0, w, h);
    g.strokeStyle = '#a8702f'; g.lineWidth = 6;
    for (var i = -8; i < 16; i++) { g.beginPath(); g.moveTo(i * 24, 0); g.lineTo(i * 24 + 140, h); g.stroke(); g.beginPath(); g.moveTo(i * 24 + 140, 0); g.lineTo(i * 24, h); g.stroke(); }
  });
  var cone = k.shadow(new T.Mesh(new T.ConeGeometry(0.17, 0.62, 40, 1, true), new T.MeshStandardMaterial({ map: waffle, roughness: 0.7, side: T.DoubleSide })));
  cone.rotation.x = Math.PI;
  var coneG = k.group(); coneG.add(cone);
  cone.position.y = 0.31;
  k.drop(coneG, 0.9, 0.6, [cp[0], by + 0.2, cp[1]], { height: 2 });
  var coneTop = by + 0.2 + 0.62;

  var scoops = [
    [0xb7cf7a, '#7c9a3c', 'Pistachio'],
    [0xf7f2e6, '#2a140a', 'Stracciatella'],
    [0xf2a3b3, '#d2566e', 'Strawberry']
  ];
  var y = coneTop + 0.1;
  scoops.forEach(function (s, i) {
    k.cap(1.8 + i * 0.9, s[2]);
    var m = k.shadow(new T.Mesh(scoopGeo(i * 3 + 1), flavour(s[0], s[1])));
    m.rotation.y = rand(i) * 6;
    k.drop(m, 1.9 + i * 0.9, 0.6, [cp[0], y, cp[1]], { squash: 0.12, height: 2 });
    y += 0.26;
  });

  // Glass cup with two scoops and a wafer.
  k.cap(4.7, 'Bacio and lemon in a cup');
  var gp = at(0.5, 0.1);
  var glass = new T.MeshPhysicalMaterial({ color: 0xffffff, roughness: 0.05, metalness: 0, transparent: true, opacity: 0.28, clearcoat: 1, side: T.DoubleSide });
  var cupProf = [[0, 0], [0.18, 0], [0.18, 0.02], [0.05, 0.05], [0.04, 0.2], [0.26, 0.42], [0.25, 0.43], [0.035, 0.22], [0.04, 0.05], [0, 0.05]].map(function (p) { return new T.Vector2(p[0], p[1]); });
  var cup = k.shadow(new T.Mesh(new T.LatheGeometry(cupProf, 48), glass));
  k.drop(cup, 4.8, 0.6, [gp[0], by, gp[1]], { height: 2 });
  [[0x6b3d22, '#3a1d0e', -0.07], [0xf6e27a, null, 0.08]].forEach(function (s, i) {
    var m = k.shadow(new T.Mesh(scoopGeo(10 + i), flavour(s[0], s[1])));
    m.scale.setScalar(0.85);
    var off = at(0.5 + s[2], 0.1 + (i ? -0.04 : 0.05));
    k.drop(m, 5.5 + i * 0.5, 0.6, [off[0], by + 0.48 + i * 0.06, off[1]], { squash: 0.12, height: 2 });
  });
  var wafer = k.shadow(new T.Mesh(new T.CylinderGeometry(0.025, 0.025, 0.42, 16), new T.MeshStandardMaterial({ map: waffle, roughness: 0.7 })));
  wafer.rotation.set(0.5, 0, 0.35);
  k.drop(wafer, 6.6, 0.6, [gp[0] + 0.05, by + 0.66, gp[1]], { spin: 0.5 });
  k.cap(7.2, 'Made in house with Italian ingredients');
  k.end(9);
});
