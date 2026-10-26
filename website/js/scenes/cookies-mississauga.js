/* Cookies: a cup of coffee is set down, then shortbread, amaretti, almond crescents and
   biscotti tumble onto the plate beside it. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  k.frame({ look: -0.68, h: 1.5, w: 2.75, elev: 0.62 });
  var py = k.plate({ r: 1.35 });

  var shortbread = k.canvasTex(256, 256, function (g, w, h) {
    g.fillStyle = '#dcae62'; g.fillRect(0, 0, w, h);
    for (var i = 0; i < 400; i++) { g.fillStyle = rand(i) > 0.5 ? 'rgba(255,248,225,.7)' : 'rgba(190,130,60,.35)'; g.beginPath(); g.arc(rand(i + 1) * w, rand(i + 2) * h, 1.5, 0, 7); g.fill(); }
  });
  var sbMat = new T.MeshStandardMaterial({ map: shortbread, roughness: 0.85 });
  var amaretto = k.canvasTex(256, 256, function (g, w, h) {
    g.fillStyle = '#b9783a'; g.fillRect(0, 0, w, h);
    g.strokeStyle = 'rgba(255,240,215,.9)'; g.lineWidth = 3;
    for (var i = 0; i < 40; i++) { g.beginPath(); g.moveTo(rand(i) * w, rand(i + 1) * h); g.lineTo(rand(i + 2) * w, rand(i + 3) * h); g.stroke(); }
  });
  var amMat = new T.MeshStandardMaterial({ map: amaretto, roughness: 0.8 });
  var dusted = k.mat(0xeedbb6, { roughness: 0.95 });
  var biscottiTex = k.canvasTex(256, 128, function (g, w, h) {
    g.fillStyle = '#e0b46e'; g.fillRect(0, 0, w, h);
    for (var i = 0; i < 18; i++) { g.fillStyle = '#f6e3b8'; g.beginPath(); g.ellipse(rand(i) * w, rand(i + 1) * h, 10, 6, rand(i + 2) * 3, 0, 7); g.fill(); g.strokeStyle = '#b9824a'; g.stroke(); }
  });
  var biMat = new T.MeshStandardMaterial({ map: biscottiTex, roughness: 0.8 });
  var crust = k.mat(0xb7793c, { roughness: 0.8 });

  // Coffee cup and saucer.
  k.cap(0.2, 'Something good beside the coffee');
  var fa = k.front(8), side = fa - Math.PI / 2;
  var cupAt = [-Math.cos(fa) * 0.55 + Math.cos(side) * 0.5, -Math.sin(fa) * 0.55 + Math.sin(side) * 0.5];
  var saucer = k.shadow(new T.Mesh(new T.CylinderGeometry(0.34, 0.26, 0.04, 48), k.M.porcelain));
  k.drop(saucer, 0.3, 0.6, [cupAt[0], py + 0.02, cupAt[1]], { height: 2 });
  var cupProf = [[0, 0], [0.13, 0], [0.16, 0.04], [0.2, 0.25], [0.21, 0.27], [0.195, 0.27], [0.185, 0.25], [0.15, 0.05], [0, 0.03]].map(function (p) { return new T.Vector2(p[0], p[1]); });
  var cup = k.group();
  cup.add(k.shadow(new T.Mesh(new T.LatheGeometry(cupProf, 48), k.M.porcelain)));
  var coffee = new T.Mesh(new T.CylinderGeometry(0.185, 0.185, 0.01, 40), k.gloss(0x3a1d0e, { roughness: 0.15 }));
  coffee.position.y = 0.23;
  var handle = k.shadow(new T.Mesh(new T.TorusGeometry(0.07, 0.018, 10, 24, Math.PI * 1.3), k.M.porcelain));
  handle.position.set(0.21, 0.14, 0);
  handle.rotation.z = -Math.PI * 0.65;
  cup.add(coffee, handle);
  k.drop(cup, 0.7, 0.6, [cupAt[0], py + 0.04, cupAt[1]], { height: 2 });

  function shortbreadCookie() {
    var geo = new T.CylinderGeometry(0.15, 0.15, 0.045, 40), pos = geo.attributes.position;
    for (var i = 0; i < pos.count; i++) { var x = pos.getX(i), z = pos.getZ(i), f = 1 + 0.05 * Math.cos(12 * Math.atan2(z, x)); pos.setX(i, x * f); pos.setZ(i, z * f); }
    geo.computeVertexNormals();
    return k.shadow(new T.Mesh(geo, sbMat));
  }
  function amarettoCookie() {
    var m = k.shadow(new T.Mesh(new T.SphereGeometry(0.11, 24, 12, 0, Math.PI * 2, 0, Math.PI / 2), amMat));
    m.scale.set(1, 0.6, 1);
    return m;
  }
  function crescent() {
    var m = k.shadow(new T.Mesh(new T.TorusGeometry(0.11, 0.035, 12, 24, Math.PI * 1.1), dusted));
    m.rotation.x = -Math.PI / 2;
    var g = k.group(); g.add(m);
    return g;
  }
  function biscotto() {
    var geo = new T.BoxGeometry(0.34, 0.03, 0.11);
    return k.shadow(new T.Mesh(geo, [crust, crust, biMat, biMat, crust, crust]));
  }

  var spots = [];
  for (var i = 0; i < 16; i++) {
    var a = i * 2.39996 + 0.4, r = 0.22 + Math.sqrt(i / 16) * 0.75;
    spots.push([Math.cos(a) * r + Math.cos(fa) * 0.15, Math.sin(a) * r + Math.sin(fa) * 0.15]);
  }
  var kinds = [
    [1.6, 'Buttery shortbread', shortbreadCookie, 4],
    [2.9, 'Classic amaretti', amarettoCookie, 4],
    [4.1, 'Almond crescents', crescent, 4],
    [5.3, 'Crunchy biscotti', biscotto, 4]
  ];
  var n = 0;
  kinds.forEach(function (kd) {
    k.cap(kd[0] - 0.1, kd[1]);
    for (var j = 0; j < kd[3]; j++, n++) {
      var s = spots[n], d = Math.hypot(s[0] - cupAt[0], s[1] - cupAt[1]);
      if (d < 0.45) { s = [s[0] * 0.5, s[1] * 0.5]; }
      var c = kd[2]();
      c.rotation.set((rand(n) - 0.5) * 0.3, rand(n + 3) * 6, (rand(n + 7) - 0.5) * 0.3);
      c.scale.multiplyScalar(1.35);
      k.drop(c, kd[0] + j * 0.18, 0.6, [s[0], py + 0.035 + (n % 3) * 0.02, s[1]], { spin: 2.4, height: 2.2 });
    }
  });
  k.cap(6.6, 'Baked fresh, perfect for sharing');
  k.end(8.4);
});
