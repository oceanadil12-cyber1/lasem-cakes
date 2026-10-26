/* Home page: La Sem's signature fruit cake, built layer by layer from the real fillings menu. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  var top0 = k.stand();
  var R = 1, SH = 0.32, CH = 0.07;
  var y1 = top0, y2 = y1 + SH + CH, y3 = y2 + SH + CH;
  var TOP = y3 + SH, ICED = TOP + 0.035, GAN = ICED + 0.03;

  var M = {
    pineapple: new T.MeshPhysicalMaterial({ map: fiber('#f7cf4a', '#e9ad21'), roughness: 0.3, clearcoat: 0.6 }),
    peach: k.gloss(0xf6a23c, { roughness: 0.3, clearcoat: 0.7 }),
    peachSkin: k.mat(0xd8502c),
    sliceFace: new T.MeshPhysicalMaterial({ map: sliceTex(), roughness: 0.3, clearcoat: 0.6 }),
    sliceSide: k.mat(0xc8152b, { roughness: 0.4 })
  };
  var slice = (function () {
    var s = new T.Shape();
    s.moveTo(0, -0.12);
    s.bezierCurveTo(0.07, -0.08, 0.11, 0.0, 0.1, 0.05);
    s.bezierCurveTo(0.09, 0.1, 0.03, 0.11, 0, 0.09);
    s.bezierCurveTo(-0.03, 0.11, -0.09, 0.1, -0.1, 0.05);
    s.bezierCurveTo(-0.11, 0.0, -0.07, -0.08, 0, -0.12);
    var g = new T.ExtrudeGeometry(s, { depth: 0.022, bevelEnabled: true, bevelThickness: 0.006, bevelSize: 0.006, bevelSegments: 2, curveSegments: 16 });
    var uv = g.attributes.uv, p = g.attributes.position;
    for (var i = 0; i < uv.count; i++) uv.setXY(i, (p.getX(i) + 0.106) / 0.212, (p.getY(i) + 0.126) / 0.242);
    return g;
  })();
  var chunk = (function () {
    var s = new T.Shape();
    s.moveTo(-0.07, -0.05); s.lineTo(0.08, -0.06); s.lineTo(0.05, 0.07); s.lineTo(-0.06, 0.05); s.closePath();
    var g = new T.ExtrudeGeometry(s, { depth: 0.09, bevelEnabled: true, bevelThickness: 0.015, bevelSize: 0.015, bevelSegments: 3 });
    g.center();
    return g;
  })();
  var peachGeo = (function () {
    var s = new T.Shape();
    s.absarc(0, 0, 0.16, Math.PI * 0.1, Math.PI * 0.9, false);
    s.absarc(0, -0.05, 0.13, Math.PI * 0.86, Math.PI * 0.14, true);
    var g = new T.ExtrudeGeometry(s, { depth: 0.035, bevelEnabled: true, bevelThickness: 0.008, bevelSize: 0.008, bevelSegments: 2, curveSegments: 24 });
    g.center();
    return g;
  })();
  var shard = (function () {
    var s = new T.Shape();
    s.moveTo(-0.05, -0.1); s.lineTo(0.06, -0.09); s.lineTo(0.08, 0.04); s.lineTo(0.01, 0.14); s.lineTo(-0.07, 0.06); s.closePath();
    var g = new T.ExtrudeGeometry(s, { depth: 0.014, bevelEnabled: false });
    g.center();
    return g;
  })();

  function ring(n, fn) { for (var i = 0; i < n; i++) fn(i, (i / n) * Math.PI * 2 + 0.2); }

  k.cap(0.2, 'European sponge, baked in house');
  k.sponge({ y: y1, t0: 0.3 });
  k.cap(1.25, 'Chantilly cream');
  k.cream({ y: y1 + SH, t0: 1.25 });
  k.cap(1.85, 'Fresh strawberries');
  ring(11, function (i, a) {
    var s = new T.Mesh(slice, [M.sliceFace, M.sliceSide]);
    s.scale.setScalar(1.35);
    s.rotation.set(-Math.PI / 2, 0, a + Math.PI / 2);
    k.drop(s, 1.85 + i * 0.06, 0.55, [Math.cos(a) * 0.86, y1 + SH + CH - 0.012, Math.sin(a) * 0.86], { spin: 1.2 });
  });
  k.sponge({ y: y2, t0: 2.75 });
  k.cream({ y: y2 + SH, t0: 3.6 });
  k.cap(4.15, 'Fresh kiwi');
  ring(10, function (i, a) {
    k.drop(k.kiwi(0.165), 4.15 + i * 0.06, 0.55, [Math.cos(a) * 0.84, y2 + SH + CH + 0.002, Math.sin(a) * 0.84], { spin: 1 });
  });
  k.sponge({ y: y3, t0: 5.0 });

  k.cap(5.95, 'Whipped cream icing');
  k.coat({ y0: top0, y1: ICED, t0: 5.95 });
  k.cap(7.05, 'Chocolate ganache');
  k.top({ y: ICED, h: GAN - ICED + 0.004, t0: 7.05 });
  k.drips({ r: R + 0.052, y: GAN - 0.012, t0: 7.35 });

  k.cap(8.1, 'Piped by hand');
  var ros = k.rosettes({ n: 12, r: 0.8, y: GAN - 0.01, t0: 8.1 });
  var rTop = GAN + 0.16;
  k.cap(9.15, 'Cherries');
  ros.forEach(function (p, i) {
    if (i % 2) return;
    var c = k.cherry();
    c.rotation.set(0.25, rand(i) * 6, 0.2);
    k.drop(c, 9.15 + i * 0.05, 0.6, [p.x, rTop + 0.07, p.z], { spin: 0.6 });
  });
  k.cap(9.8, 'Strawberries');
  ros.forEach(function (p, i) {
    if (!(i % 2)) return;
    var s = k.strawberry(0.95);
    s.rotation.set(Math.PI + 0.35, 0, 0);
    var h = k.group(); h.add(s);
    h.rotation.y = Math.atan2(p.x, p.z);
    k.drop(h, 9.8 + i * 0.05, 0.6, [p.x, rTop + 0.11, p.z], { spin: 0.8 });
  });
  k.cap(10.55, 'Pineapple chunks');
  [[0.22, 0.12], [-0.18, 0.26], [-0.28, -0.12], [0.1, -0.3], [0.34, -0.16]].forEach(function (xz, i) {
    var p = k.shadow(new T.Mesh(chunk, M.pineapple));
    p.rotation.set(rand(i) * 0.8 - 0.4, rand(i + 9) * 6, rand(i + 3) * 0.6);
    k.drop(p, 10.55 + i * 0.09, 0.62, [xz[0], GAN + 0.06, xz[1]], { spin: 2.2 });
  });
  k.cap(11.05, 'Peach slices');
  [[-0.05, 0.05, 0.4], [0.3, 0.32, -0.6], [-0.4, 0.1, 1.9]].forEach(function (v, i) {
    var p = k.shadow(new T.Mesh(peachGeo, [M.peach, M.peachSkin]));
    p.rotation.set(-Math.PI / 2 + 0.5, 0, v[2]);
    k.drop(p, 11.05 + i * 0.1, 0.6, [v[0], GAN + 0.09, v[1]], { spin: 1.6 });
  });
  [[0.18, -0.2, 0.3], [-0.26, -0.16, -0.5]].forEach(function (v, i) {
    var kw = k.kiwi(0.15);
    kw.rotation.set(Math.PI / 2 - 0.35, v[2], 0);
    k.drop(kw, 11.45 + i * 0.1, 0.6, [v[0], GAN + 0.16, v[1]], { spin: 1.4 });
  });
  k.cap(11.85, 'Blueberries & fresh mint');
  for (var b = 0; b < 11; b++) {
    var ang = rand(b + 200) * Math.PI * 2, rad = 0.12 + rand(b + 300) * 0.38;
    k.drop(k.blueberry(), 11.85 + b * 0.05, 0.55, [Math.cos(ang) * rad, GAN + 0.075 + rand(b) * 0.06, Math.sin(ang) * rad], { spin: 3 });
  }
  [[0.12, 0.05, 0.5], [-0.22, -0.02, 2.4], [0.05, 0.3, 4.1]].forEach(function (v, i) {
    var l = k.leaf();
    l.rotation.set(-Math.PI / 2 + 0.6, 0, v[2]);
    k.drop(l, 12.35 + i * 0.12, 0.9, [v[0], GAN + 0.2, v[1]], { spin: 1, flutter: true });
  });
  k.cap(12.85, 'Dark chocolate');
  [[-0.32, 0.3, 0.6], [0.36, 0.1, -0.9], [0.0, -0.36, 2.2]].forEach(function (v, i) {
    var s = k.shadow(new T.Mesh(shard, k.M.ganache));
    s.rotation.set(0.15, v[2], 0.25);
    k.drop(s, 12.85 + i * 0.1, 0.5, [v[0], GAN + 0.12, v[1]], { spin: 0.5 });
  });
  k.cap(13.3, 'Finished by hand, since 1963');
  var pl = k.plaque({ text: 'La Sem', sub: 'SINCE 1963' });
  pl.rotation.set(-0.42, 0, 0);
  var ph = k.group(); ph.add(pl);
  ph.rotation.y = k.facing(14.6);
  k.drop(ph, 13.3, 0.7, [0, GAN + 0.25, 0.06], { spin: 0.4 });
  var dg = new T.SphereGeometry(0.018, 12, 10);
  for (var g = 0; g < 28; g++) {
    var ga = rand(g + 500) * Math.PI * 2, gr = 0.1 + rand(g + 600) * 0.82;
    k.drop(new T.Mesh(dg, k.M.gold), 13.8 + rand(g + 700) * 0.6, 0.5, [Math.cos(ga) * gr, GAN + 0.02 + (gr > 0.7 ? 0.12 : 0.04), Math.sin(ga) * gr], { spin: 4 });
  }
  k.end(15);
  k.cap(14.6, 'Ready for your celebration');

  function fiber(a, b) {
    return k.canvasTex(128, 128, function (g, w, h) {
      g.fillStyle = a; g.fillRect(0, 0, w, h);
      for (var i = 0; i < 60; i++) {
        g.strokeStyle = b; g.globalAlpha = 0.25 + rand(i) * 0.35; g.lineWidth = 1 + rand(i + 1) * 1.5;
        var x = rand(i + 2) * w;
        g.beginPath(); g.moveTo(x, 0); g.lineTo(x + (rand(i + 3) - 0.5) * 20, h); g.stroke();
      }
      g.globalAlpha = 1;
    });
  }
  function sliceTex() {
    return k.canvasTex(256, 256, function (g, w, h) {
      g.fillStyle = '#d0142b'; g.fillRect(0, 0, w, h);
      var grd = g.createRadialGradient(w / 2, h * 0.55, 10, w / 2, h * 0.55, w * 0.46);
      grd.addColorStop(0, '#fff2ec'); grd.addColorStop(0.35, '#ffb3b0'); grd.addColorStop(0.75, '#ef4d5a'); grd.addColorStop(1, '#c50f27');
      g.fillStyle = grd; g.fillRect(0, 0, w, h);
      g.strokeStyle = 'rgba(255,255,255,0.5)'; g.lineWidth = 2;
      for (var i = 0; i < 10; i++) {
        var a = (i / 10) * Math.PI * 2;
        g.beginPath(); g.moveTo(w / 2, h * 0.55); g.lineTo(w / 2 + Math.cos(a) * w * 0.32, h * 0.55 + Math.sin(a) * h * 0.32); g.stroke();
      }
    });
  }
});
