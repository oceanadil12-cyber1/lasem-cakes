/* Baptism cakes: two white tiers with soft blue ribbons, blue rosettes and a gold cross. */
LaSem3D.start(function (k) {
  var T = k.T;
  k.frame({ look: 0.8, h: 3.6, w: 2.9 });
  k.rimColor('#d8c08a');
  var base = k.stand();
  var fondant = k.mat(0xfbf7f0, { roughness: 0.4 });
  var blue = k.gloss(0x9cc8e8, { roughness: 0.35, clearcoat: 0.5 });
  var blueCream = k.mat(0xbcdaf0);

  k.cap(0.2, 'Two tiers of sponge');
  var top1 = k.tier({ r: 0.95, h: 0.5, y: base, t0: 0.3, mat: fondant });
  k.cap(1.1, 'Smooth white fondant');
  var top2 = k.tier({ r: 0.62, h: 0.44, y: top1, t0: 2.0, mat: fondant });

  k.cap(3.4, 'Soft blue ribbons');
  [[0.987, base + 0.04, 3.4], [0.657, top1 + 0.04, 3.7]].forEach(function (b) {
    var g = new T.CylinderGeometry(b[0], b[0], 0.08, 96, 1, true);
    g.translate(0, 0.04, 0);
    var m = k.shadow(new T.Mesh(g, blue));
    m.position.y = b[1];
    k.rise(m, b[2], 0.45);
  });
  k.pearls({ r: 0.99, y: top1 - 0.08, n: 44, size: 0.025, t0: 4.0, step: 0.01, mat: k.M.gold });

  k.cap(4.6, 'Blue rosettes');
  k.rosettes({ n: 12, r: 0.81, y: top1, t0: 4.6, mat: blueCream, scale: 0.9 });
  k.rosettes({ n: 10, r: 0.5, y: top2, t0: 5.6, mat: k.M.rosette, scale: 0.7 });

  k.cap(6.6, 'A gold cross');
  var cross = k.group();
  var v = k.shadow(new T.Mesh(new T.BoxGeometry(0.075, 0.48, 0.055), k.M.gold));
  v.position.y = 0.24;
  var h = k.shadow(new T.Mesh(new T.BoxGeometry(0.3, 0.075, 0.055), k.M.gold));
  h.position.y = 0.33;
  cross.add(v, h);
  cross.rotation.y = k.facing(8.6);
  k.drop(cross, 6.7, 0.8, [0, top2 - 0.02, 0], { spin: 0.6, height: 2.2 });
  k.sparkle({ t: 7.5, y: top2, r: 0.7, n: 40 });
  k.cap(7.9, 'Blessed and beautiful');
  k.end(9.8);
});
