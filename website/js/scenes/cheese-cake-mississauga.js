/* Cheesecake: the graham crust is pressed in, the New York filling rises, strawberry glaze
   pours over the top and drips, then fresh strawberries and mint finish it. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  var base = k.stand();
  var crumbTex = k.canvasTex(512, 128, function (g, w, h) {
    g.fillStyle = '#b07a3e'; g.fillRect(0, 0, w, h);
    for (var i = 0; i < 1600; i++) { g.fillStyle = rand(i) > 0.5 ? 'rgba(80,45,15,.4)' : 'rgba(230,185,120,.45)'; g.beginPath(); g.arc(rand(i + 1) * w, rand(i + 2) * h, 1 + rand(i + 3) * 2.5, 0, 7); g.fill(); }
  });
  crumbTex.wrapS = T.RepeatWrapping;
  var crumb = new T.MeshStandardMaterial({ map: crumbTex, roughness: 0.95 });
  var filling = k.mat(0xf8edd6, { roughness: 0.55 });
  var glaze = k.gloss(0xc0182c, { roughness: 0.08, clearcoat: 1 });

  k.cap(0.2, 'Graham cracker crust');
  var cg = new T.CylinderGeometry(1, 1, 0.13, 96);
  cg.translate(0, 0.065, 0);
  var crust = k.shadow(new T.Mesh(cg, crumb));
  crust.position.y = base;
  k.spread(crust, 0.3, 0.7);
  k.burst({ t: 0.5, y: base, r: 1, color: 0xd9a868 });

  k.cap(1.3, 'Creamy New York style filling');
  var fg = new T.CylinderGeometry(0.99, 0.99, 0.62, 96);
  fg.translate(0, 0.31, 0);
  var fill = k.shadow(new T.Mesh(fg, filling));
  fill.position.y = base + 0.13;
  k.rise(fill, 1.4, 1.6);
  var top = base + 0.75;

  k.cap(3.3, 'Strawberry glaze');
  var gt = k.top({ y: top, h: 0.04, t0: 3.3, dur: 0.7, mat: glaze, r: 1.0 });
  k.drips({ r: 0.995, y: gt - 0.015, t0: 3.7, mat: glaze, n: 20, max: 0.32, thick: 0.034 });

  k.cap(5.0, 'Fresh strawberries');
  for (var i = 0; i < 10; i++) {
    var a = (i / 10) * Math.PI * 2;
    var s = k.strawberry(1.15);
    s.rotation.set(Math.PI / 2 + 0.3, 0, 0);
    var h = k.group(); h.add(s);
    h.rotation.y = -a + Math.PI / 2;
    k.drop(h, 5.1 + i * 0.1, 0.55, [Math.cos(a) * 0.74, gt + 0.09, Math.sin(a) * 0.74], { spin: 1 });
  }
  var center = k.strawberry(1.5);
  center.rotation.set(Math.PI, 0, 0);
  k.drop(center, 6.3, 0.6, [0, gt + 0.2, 0], { spin: 0.6 });
  k.cap(6.8, 'And a sprig of mint');
  [0, 2.1, 4.2].forEach(function (r, i) {
    var l = k.leaf(null, 1.3);
    l.rotation.set(-Math.PI / 2 + 0.5, 0, r);
    k.drop(l, 6.9 + i * 0.12, 0.8, [Math.cos(r) * 0.12, gt + 0.06, Math.sin(r) * 0.12], { flutter: true, spin: 1 });
  });
  k.cap(7.8, 'New York style, made in house');
  k.end(9.2);
});
