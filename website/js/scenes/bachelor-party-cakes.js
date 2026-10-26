/* Bachelor & bachelorette cakes: a glossy black cake with a gold drip; a champagne bottle
   lands on top and the cork pops in a shower of gold. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  k.frame({ look: 0.85, h: 3.9, w: 3.0 });
  k.rimColor('#f2d27c');
  var base = k.stand({ mat: k.gloss(0x1b1719, { roughness: 0.3 }) });
  var black = k.gloss(0x141113, { roughness: 0.28, clearcoat: 0.8 });
  var gold = k.metal(0xdcb766, 0.22);

  k.cap(0.2, 'Dark chocolate sponge');
  var y = k.sponge({ y: base, h: 0.3, t0: 0.3, flavour: 'chocolate' });
  k.cap(1.0, 'Grand Marnier cream');
  y = k.cream({ y: y, t0: 1.0, mat: k.mat(0xf2b25e) });
  y = k.sponge({ y: y, h: 0.3, t0: 1.6, flavour: 'chocolate' });
  k.cap(2.5, 'Black velvet finish');
  k.coat({ y0: base, y1: y + 0.035, t0: 2.5, mat: black });
  var top = y + 0.035;
  k.cap(3.5, 'Liquid gold');
  var gt = k.top({ y: top, t0: 3.5, mat: gold });
  k.drips({ r: 1.052, y: gt - 0.012, t0: 3.8, mat: gold, n: 24, max: 0.5 });
  k.rosettes({ n: 12, r: 0.84, y: gt, t0: 4.8, mat: k.gloss(0x1e1a1c, { roughness: 0.4 }), scale: 0.9 });
  for (var d = 0; d < 26; d++) {
    var a = rand(d + 40) * Math.PI * 2, rr = 0.2 + rand(d + 50) * 0.5;
    k.drop(new T.Mesh(new T.SphereGeometry(0.02, 10, 8), gold), 5.6 + rand(d) * 0.5, 0.5, [Math.cos(a) * rr, gt + 0.02, Math.sin(a) * rr], { spin: 4 });
  }

  // Champagne bottle.
  k.cap(6.4, 'Pop the cork…');
  var glass = k.gloss(0x0f3b22, { roughness: 0.1, clearcoat: 1, envMapIntensity: 1.5 });
  var prof = [[0, 0], [0.13, 0], [0.135, 0.02], [0.135, 0.42], [0.12, 0.5], [0.07, 0.6], [0.045, 0.68], [0.045, 0.86], [0.05, 0.88], [0.0, 0.88]]
    .map(function (q) { return new T.Vector2(q[0], q[1]); });
  var bottle = k.group();
  bottle.add(k.shadow(new T.Mesh(new T.LatheGeometry(prof, 40), glass)));
  var foil = new T.Mesh(new T.CylinderGeometry(0.052, 0.07, 0.2, 24), gold);
  foil.position.y = 0.79;
  var label = new T.Mesh(new T.CylinderGeometry(0.138, 0.138, 0.16, 40, 1, true), k.mat(0xf3ead6));
  label.position.y = 0.26;
  bottle.add(foil, label);
  bottle.rotation.y = k.facing(9);
  k.drop(bottle, 6.5, 0.8, [0, gt, 0], { spin: 0.4, height: 2.4 });
  var cork = k.shadow(new T.Mesh(new T.CylinderGeometry(0.04, 0.045, 0.07, 16), k.mat(0xc69a62)));
  var corkAt = gt + 0.92;
  k.anim(cork, 6.5, 2.6, function (q, obj, time) {
    if (time < 8.2) { obj.visible = time > 6.95; obj.position.set(0, corkAt, 0); obj.rotation.set(0, 0, 0); return; }
    var s = time - 8.2;
    obj.position.set(s * 0.9, corkAt + s * 3.2 - s * s * 2.2, s * 0.4);
    obj.rotation.set(s * 9, 0, s * 7);
    obj.visible = s < 1.6;
  });
  k.cap(8.2, '…and celebrate');
  k.confetti({ t: 8.2, n: 170, from: 3.2, colors: ['#f2d27c', '#dcb766', '#fff3cf', '#e45d8c'] });
  k.sparkle({ t: 8.2, y: gt + 0.6, r: 0.6, n: 50 });
  k.end(11);
});
