/* Croissants: golden crescents land on the board one by one: plain, chocolate with a
   drizzle, chocolate hazelnut, and almond with flaked almonds and icing sugar. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  k.frame({ look: -0.7, h: 1.5, w: 2.75, elev: 0.62 });
  var by = k.board({ w: 2.7, d: 1.9 });

  // A crescent of rolled, layered dough: thick in the middle, tapering to the tips, with ridges.
  function croissantGeo() {
    var U = 64, V = 24, pos = [], col = [], idx = [], c = new T.Color();
    for (var i = 0; i <= U; i++) {
      var u = i / U, ang = Math.PI * (0.15 + 0.7 * u);
      var cx = Math.cos(ang) * 0.3, cz = -Math.sin(ang) * 0.3 + 0.18;
      var ridge = 0.5 + 0.5 * Math.cos(u * Math.PI * 2 * 4.5);
      var r = (0.03 + 0.12 * Math.pow(Math.sin(Math.PI * u), 0.9)) * (1 + 0.1 * ridge);
      for (var j = 0; j <= V; j++) {
        var v = (j / V) * Math.PI * 2;
        var nx = Math.cos(ang), nz = -Math.sin(ang);
        pos.push(cx + nx * Math.cos(v) * r, Math.max(Math.sin(v) * r * 0.85, -0.01) + 0.01, cz + nz * Math.cos(v) * r);
        c.set(ridge > 0.6 ? 0xb8742f : 0xd99b4b).convertSRGBToLinear();
        col.push(c.r, c.g, c.b);
      }
    }
    for (var a = 0; a < U; a++) for (var b = 0; b < V; b++) {
      var p0 = a * (V + 1) + b, p1 = p0 + V + 1;
      idx.push(p0, p1, p0 + 1, p1, p1 + 1, p0 + 1);
    }
    var geo = new T.BufferGeometry();
    geo.setAttribute('position', new T.Float32BufferAttribute(pos, 3));
    geo.setAttribute('color', new T.Float32BufferAttribute(col, 3));
    geo.setIndex(idx);
    geo.computeVertexNormals();
    return geo;
  }
  var geo = croissantGeo();
  var dough = new T.MeshPhysicalMaterial({ vertexColors: true, roughness: 0.45, clearcoat: 0.35 });
  var choc = k.gloss(0x2a140a, { roughness: 0.25 });
  var flake = k.mat(0xf1dcb1, { roughness: 0.7 });
  var fa = k.front(8.5), side = fa - Math.PI / 2;
  function at(x, z) { return [Math.cos(side) * x + Math.cos(fa) * z, Math.sin(side) * x + Math.sin(fa) * z]; }

  var list = [
    [-0.62, 0.22, 0.3, 'Butter croissant, laminated by hand', null],
    [0.62, 0.3, 1.4, 'Chocolate croissant', 'drizzle'],
    [-0.5, -0.5, 2.6, 'Chocolate hazelnut', 'bits'],
    [0.58, -0.45, 3.8, 'Almond, with flaked almonds', 'almond']
  ];
  list.forEach(function (c, i) {
    var g = k.group(), m = k.shadow(new T.Mesh(geo, dough));
    g.add(m);
    g.scale.setScalar(1.35);
    var p = at(c[0], c[1]);
    g.rotation.y = -fa + (rand(i) - 0.5) * 1.2;
    k.cap(c[2] - 0.1, c[3]);
    k.drop(g, c[2], 0.7, [p[0], by, p[1]], { spin: 1.6, height: 2.3, squash: 0.1 });
    if (c[4] === 'drizzle') {
      for (var s = 0; s < 6; s++) {
        // Each line runs across the croissant, from the inside of the curve to the outside.
        var ang = Math.PI * (0.3 + s * 0.08);
        var line = new T.Mesh(new T.CylinderGeometry(0.008, 0.008, 0.26, 6), choc);
        line.rotation.set(0, ang, Math.PI / 2);
        line.position.set(Math.cos(ang) * 0.3, 0.125, -Math.sin(ang) * 0.3 + 0.18);
        g.add(line);
        k.grow(line, c[2] + 0.8 + s * 0.08, 0.3);
      }
    }
    if (c[4] === 'bits' || c[4] === 'almond') {
      for (var b = 0; b < 16; b++) {
        var u = 0.25 + rand(b + i * 9) * 0.5, an = Math.PI * (0.15 + 0.7 * u);
        var bit = c[4] === 'almond' ? new T.Mesh(new T.CylinderGeometry(0.03, 0.03, 0.006, 10), flake) : new T.Mesh(new T.BoxGeometry(0.02, 0.012, 0.02), choc);
        bit.position.set(Math.cos(an) * 0.3 + (rand(b) - 0.5) * 0.06, 0.13 + rand(b + 2) * 0.02, -Math.sin(an) * 0.3 + 0.18 + (rand(b + 4) - 0.5) * 0.06);
        bit.rotation.set(rand(b) * 0.6, rand(b + 1) * 6, rand(b + 2) * 0.6);
        g.add(bit);
        k.pop(bit, c[2] + 0.8 + b * 0.03, 0.25);
      }
    }
    if (c[4] === 'almond') {
      var gp = at(c[0], c[1]);
      k.burst({ t: c[2] + 1.5, y: by + 0.35, r: 0.05, n: 60, color: 0xffffff, size: 0.03, up: -0.2, speed: 0.35, x: gp[0], z: gp[1], life: 1.4 });
    }
  });
  k.cap(5.6, 'Baked fresh this morning');
  k.end(7.6);
});
