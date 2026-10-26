/* Pastries: a gold doily on a plate, then the pastry case fills it: Sicilian cannoli, fruit
   tarts, cream puffs, an éclair and a rum ball. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  k.frame({ look: -0.7, h: 1.45, w: 2.75, elev: 0.62 });
  var py = k.plate({ r: 1.35 });
  var doily = k.shadow(new T.Mesh(new T.CylinderGeometry(1.12, 1.12, 0.008, 72), k.metal(0xe3c27a, 0.4)));
  doily.position.y = py + 0.004;
  k.spread(doily, 0.2, 0.5);
  var y0 = py + 0.01;

  var pastry = k.gloss(0xc98a3e, { roughness: 0.45, clearcoat: 0.3 });
  var ricotta = k.mat(0xfbf3e3, { roughness: 0.8 });
  var choc = k.gloss(0x2a140a, { roughness: 0.3 });
  var cream = k.mat(0xfff3d6, { roughness: 0.6 });
  var sugar = k.mat(0xffffff, { roughness: 0.9 });

  function cannoli() {
    var g = k.group();
    var shell = k.shadow(new T.Mesh(new T.CylinderGeometry(0.075, 0.075, 0.34, 24, 1, true), pastry));
    shell.rotation.z = Math.PI / 2;
    g.add(shell);
    [-1, 1].forEach(function (s) {
      var end = k.shadow(new T.Mesh(new T.SphereGeometry(0.078, 16, 12), ricotta));
      end.scale.set(0.7, 1, 1);
      end.position.x = s * 0.17;
      g.add(end);
      for (var i = 0; i < 5; i++) {
        var chip = new T.Mesh(new T.SphereGeometry(0.012, 6, 5), choc);
        var a = rand(i + s * 10) * Math.PI * 2;
        chip.position.set(s * (0.19 + rand(i) * 0.02), Math.cos(a) * 0.05, Math.sin(a) * 0.05);
        g.add(chip);
      }
    });
    g.position.y = 0.075;
    var h = k.group(); h.add(g);
    return h;
  }
  function tart() {
    var g = k.group(), prof = [];
    for (var i = 0; i <= 10; i++) { var q = i / 10; prof.push(new T.Vector2(0.13 - 0.03 * (1 - q) * (1 - q), q * 0.07)); }
    var geo = new T.LatheGeometry(prof, 48), pos = geo.attributes.position;
    for (var j = 0; j < pos.count; j++) { var x = pos.getX(j), z = pos.getZ(j), f = 1 + 0.05 * Math.cos(16 * Math.atan2(z, x)); pos.setX(j, x * f); pos.setZ(j, z * f); }
    geo.computeVertexNormals();
    g.add(k.shadow(new T.Mesh(geo, pastry)));
    var c = new T.Mesh(new T.CylinderGeometry(0.115, 0.115, 0.03, 32), cream);
    c.position.y = 0.065;
    g.add(c);
    var st = k.strawberry(0.55); st.position.set(0.03, 0.12, 0); st.rotation.z = 0.4; g.add(st);
    var kw = k.kiwi(0.05); kw.position.set(-0.05, 0.1, 0.03); kw.rotation.x = 0.8; g.add(kw);
    var bb = k.blueberry(); bb.scale.setScalar(0.6); bb.position.set(-0.03, 0.095, -0.05); g.add(bb);
    return g;
  }
  function puff(topMat) {
    var g = k.group();
    var b = k.shadow(new T.Mesh(new T.SphereGeometry(0.12, 24, 16), pastry));
    b.scale.set(1, 0.78, 1);
    b.position.y = 0.09;
    var top = new T.Mesh(new T.SphereGeometry(0.122, 24, 12, 0, Math.PI * 2, 0, Math.PI * 0.32), topMat);
    top.scale.set(1, 0.78, 1);
    top.position.y = 0.09;
    g.add(b, top);
    return g;
  }
  function eclair() {
    var g = k.group();
    var body = k.shadow(new T.Mesh(new T.CylinderGeometry(0.07, 0.07, 0.32, 20), pastry));
    body.rotation.z = Math.PI / 2;
    body.position.y = 0.07;
    g.add(body);
    [-1, 1].forEach(function (s) { var e = k.shadow(new T.Mesh(new T.SphereGeometry(0.07, 16, 12), pastry)); e.position.set(s * 0.16, 0.07, 0); g.add(e); });
    var glaze = new T.Mesh(new T.CylinderGeometry(0.072, 0.072, 0.32, 20, 1, false, 0, Math.PI), choc);
    glaze.rotation.set(Math.PI / 2, Math.PI / 2, 0);
    glaze.rotation.set(0, 0, Math.PI / 2);
    glaze.position.y = 0.075;
    glaze.scale.set(1, 1, 1);
    g.add(glaze);
    return g;
  }
  function rumBall() {
    var tex = k.canvasTex(256, 128, function (g, w, h) {
      g.fillStyle = '#2b150b'; g.fillRect(0, 0, w, h);
      for (var i = 0; i < 500; i++) { g.save(); g.translate(rand(i) * w, rand(i + 1) * h); g.rotate(rand(i + 2) * 6); g.fillStyle = '#4a2716'; g.fillRect(-4, -1, 8, 2); g.restore(); }
    });
    var m = k.shadow(new T.Mesh(new T.SphereGeometry(0.1, 24, 16), new T.MeshStandardMaterial({ map: tex, roughness: 0.8 })));
    m.position.y = 0.1;
    var g = k.group(); g.add(m);
    return g;
  }

  var slots = [[0, 0], [-0.55, -0.1], [0.55, 0.1], [-0.25, 0.5], [0.3, -0.5], [-0.3, -0.55], [0.32, 0.55], [-0.8, 0.35], [0.82, -0.32]];
  var plan = [
    [0.8, 'Sicilian cannoli with ricotta', cannoli], [1.2, null, cannoli],
    [2.0, 'Fresh fruit tarts', tart], [2.4, null, tart],
    [3.2, 'Cream puffs: hazelnut and vanilla', function () { return puff(choc); }], [3.6, null, function () { return puff(sugar); }],
    [4.4, 'Chocolate éclair', eclair],
    [5.2, 'Rum balls', rumBall], [5.6, null, tart]
  ];
  plan.forEach(function (p, i) {
    if (p[1]) k.cap(p[0] - 0.1, p[1]);
    var obj = p[2]();
    obj.rotation.y = rand(i + 5) * Math.PI * 2;
    obj.scale.setScalar(1.45);
    k.drop(obj, p[0], 0.7, [slots[i][0], y0, slots[i][1]], { spin: 1.2, height: 2.4 });
  });
  k.sparkle({ t: 6.5, y: y0, r: 1.1, n: 30, color: 0xfff1d6 });
  k.cap(6.6, 'Made fresh every day');
  k.end(8.5);
});
