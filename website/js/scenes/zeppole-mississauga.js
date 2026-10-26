/* Zeppole: rings of choux pastry are piped, filled with vanilla custard (and sweet ricotta),
   topped with an amarena cherry and dusted with sugar. Mr. Ventola's recipe. */
LaSem3D.start(function (k) {
  var T = k.T;
  k.frame({ look: -0.68, h: 1.45, w: 2.6, elev: 0.68 });
  var py = k.plate({ r: 1.3 });

  // Piped choux ring: a torus with ridges like dough from a star nozzle.
  var ringGeo = new T.TorusGeometry(0.21, 0.085, 18, 64);
  (function () {
    var pos = ringGeo.attributes.position, c = new T.Vector3(), p = new T.Vector3();
    for (var i = 0; i < pos.count; i++) {
      p.fromBufferAttribute(pos, i);
      var a = Math.atan2(p.y, p.x);
      c.set(Math.cos(a) * 0.21, Math.sin(a) * 0.21, 0);
      var d = p.clone().sub(c), tube = Math.atan2(d.z, d.length() * Math.sign(d.dot(c)) || 1);
      var f = 1 + 0.13 * Math.cos(9 * Math.atan2(d.z, d.clone().setZ(0).length() * (d.dot(c) > 0 ? 1 : -1)) + a * 3);
      p.copy(c).add(d.multiplyScalar(f));
      pos.setXYZ(i, p.x, p.y, p.z);
    }
    ringGeo.rotateX(Math.PI / 2);
    ringGeo.computeVertexNormals();
  })();
  var choux = k.gloss(0xd29a4c, { roughness: 0.5, clearcoat: 0.25 });
  var custard = k.mat(0xf6d679, { roughness: 0.45 });
  var ricotta = k.mat(0xfbf4e6, { roughness: 0.8 });
  var amarena = k.gloss(0x4a0612, { roughness: 0.12 });
  var syrup = k.gloss(0x5e0a18, { roughness: 0.08 });
  var fillGeo = k.G.rosetteGeo(0.13, 0.14, 8);

  function zeppola(x, z, s, t0, filling) {
    var y = py + 0.085 * s;
    var ring = k.shadow(new T.Mesh(ringGeo, choux));
    ring.scale.setScalar(s);
    ring.position.set(x, y, z);
    // The ring is piped round: it grows in a spin.
    k.anim(ring, t0, 0.9, function (q) {
      var e = k.easeOut(q);
      ring.scale.set(s * (0.3 + 0.7 * e), s * e, s * (0.3 + 0.7 * e));
      ring.rotation.y = (1 - e) * 4;
    });
    var fill = k.shadow(new T.Mesh(fillGeo, filling === 'ricotta' ? ricotta : custard));
    fill.scale.setScalar(s);
    fill.position.set(x, y + 0.03 * s, z);
    k.pipe(fill, t0 + 1.2, 0.5);
    if (filling === 'ricotta') {
      for (var i = 0; i < 8; i++) {
        var chip = new T.Mesh(new T.SphereGeometry(0.012 * s, 6, 5), k.M.ganache);
        var a = i * 0.8;
        chip.position.set(x + Math.cos(a) * 0.06 * s, y + (0.06 + (i % 3) * 0.03) * s, z + Math.sin(a) * 0.06 * s);
        k.pop(chip, t0 + 1.7 + i * 0.03, 0.2);
      }
    }
    var cherry = k.shadow(new T.Mesh(new T.SphereGeometry(0.05 * s, 18, 14), amarena));
    k.drop(cherry, t0 + 1.9, 0.55, [x, y + 0.19 * s, z], { height: 1.8 });
    var drip = new T.Mesh(new T.CylinderGeometry(0.012 * s, 0.006 * s, 0.07 * s, 8), syrup);
    drip.geometry.translate(0, -0.035 * s, 0);
    drip.position.set(x + 0.035 * s, y + 0.17 * s, z);
    k.grow(drip, t0 + 2.4, 0.5);
    k.burst({ t: t0 + 2.6, y: y + 0.2 * s, r: 0.05, n: 40, color: 0xffffff, size: 0.035, up: 0.6, speed: 0.4, x: x, z: z, life: 1.2 });
  }

  var fa = k.front(7.5);
  var side = fa - Math.PI / 2;
  k.cap(0.3, 'Light choux pastry, piped in rings');
  zeppola(0, 0, 1.45, 0.4, 'vanilla');
  k.cap(1.6, 'Vanilla custard');
  k.cap(2.3, 'An amarena cherry');
  k.cap(3.0, 'A dusting of sugar');
  k.cap(3.8, 'Or filled with sweet ricotta');
  zeppola(Math.cos(side) * 0.68 - Math.cos(fa) * 0.25, Math.sin(side) * 0.68 - Math.sin(fa) * 0.25, 1, 3.8, 'ricotta');
  zeppola(-Math.cos(side) * 0.66 - Math.cos(fa) * 0.2, -Math.sin(side) * 0.66 - Math.sin(fa) * 0.2, 1, 4.4, 'vanilla');
  k.cap(7.0, 'Mr. Ventola’s original recipe');
  k.end(8.6);
});
