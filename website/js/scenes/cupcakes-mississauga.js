/* Cupcakes: paper liners pop up, the cakes rise as if in the oven, buttercream swirls are
   piped on, sprinkles rain down and a cherry goes on top. */
LaSem3D.start(function (k) {
  var T = k.T;
  k.frame({ look: -0.55, h: 1.45, w: 2.1, elev: 0.6 });
  var py = k.plate({ r: 1.3 });

  // Pleated paper liner.
  function linerGeo() {
    var prof = [new T.Vector2(0.001, 0), new T.Vector2(0.15, 0), new T.Vector2(0.2, 0.22), new T.Vector2(0.001, 0.22)];
    var geo = new T.LatheGeometry(prof, 96), pos = geo.attributes.position;
    for (var i = 0; i < pos.count; i++) { var x = pos.getX(i), z = pos.getZ(i), f = 1 + 0.035 * Math.cos(28 * Math.atan2(z, x)); pos.setX(i, x * f); pos.setZ(i, z * f); }
    geo.computeVertexNormals();
    return geo;
  }
  // Soft-serve style buttercream swirl with ridges that twist as they rise.
  function swirlGeo() {
    var prof = [], H = 0.34;
    for (var i = 0; i <= 40; i++) {
      var q = i / 40;
      var r = 0.2 * Math.pow(1 - q, 0.75) * (1 + 0.16 * Math.sin(q * Math.PI * 3.2)) + 0.002;
      prof.push(new T.Vector2(r, q * H));
    }
    var geo = new T.LatheGeometry(prof, 72), pos = geo.attributes.position;
    for (var j = 0; j < pos.count; j++) {
      var x = pos.getX(j), y = pos.getY(j), z = pos.getZ(j);
      var f = 1 + 0.09 * Math.cos(7 * Math.atan2(z, x) + y * 30);
      pos.setX(j, x * f); pos.setZ(j, z * f);
    }
    geo.computeVertexNormals();
    return { geo: geo, H: H };
  }
  var liner = linerGeo(), swirl = swirlGeo();
  var cakes = [
    { x: 0, z: 0, paper: 0xf2a9c2, sponge: 0xe7bf74, cream: 0xf9cfe0, label: 'Vanilla with strawberry buttercream' },
    { x: -0.58, z: 0.18, paper: 0x6b3a22, sponge: 0x4d2a19, cream: 0x6b4026, label: 'Chocolate with chocolate buttercream' },
    { x: 0.58, z: 0.18, paper: 0xc23a48, sponge: 0x9e1b25, cream: 0xfbf3e8, label: 'Red velvet with cream cheese icing' }
  ];
  var fa = k.front(8.5), side = fa - Math.PI / 2;
  function place(c) { return [Math.cos(side) * c.x + Math.cos(fa) * c.z, Math.sin(side) * c.x + Math.sin(fa) * c.z]; }

  k.cap(0.2, 'Paper liners');
  cakes.forEach(function (c, i) {
    var p = place(c);
    var l = k.shadow(new T.Mesh(liner, k.mat(c.paper, { roughness: 0.6 })));
    l.position.set(p[0], py, p[1]);
    k.pop(l, 0.3 + i * 0.2, 0.45);
    // The cake domes up out of the liner.
    var dome = k.shadow(new T.Mesh(new T.SphereGeometry(0.205, 32, 16, 0, Math.PI * 2, 0, Math.PI / 2), k.mat(c.sponge, { roughness: 0.9 })));
    dome.scale.set(1, 0.55, 1);
    dome.position.set(p[0], py + 0.2, p[1]);
    k.rise(dome, 1.3 + i * 0.15, 1.2);
    var cream = k.shadow(new T.Mesh(swirl.geo, k.mat(c.cream, { roughness: 0.5 })));
    cream.position.set(p[0], py + 0.24, p[1]);
    k.anim(cream, 3.0 + i * 0.6, 0.9, function (q) {
      var e = k.easeOut(q);
      cream.scale.set(0.6 + 0.4 * e, Math.max(e, 0.01), 0.6 + 0.4 * e);
      cream.rotation.y = (1 - e) * 5;
    });
    k.sprinkles({ x: p[0], z: p[1], r: 0.17, y: py + 0.24, yAt: function (rr) { return py + 0.24 + Math.max(0, 0.3 - rr * 1.6); }, t0: 5.2 + i * 0.2, n: 18, span: 0.6 });
    if (i === 0) {
      var ch = k.cherry();
      k.drop(ch, 6.4, 0.6, [p[0], py + 0.24 + swirl.H + 0.06, p[1]], { spin: 0.5, height: 1.8 });
    }
  });
  k.cap(1.3, 'Baked until they rise');
  k.cap(3.0, cakes[0].label);
  k.cap(3.6, cakes[1].label);
  k.cap(4.2, cakes[2].label);
  k.cap(5.2, 'Sprinkles');
  k.cap(6.4, 'A cherry on top');
  k.end(8.2);
});
