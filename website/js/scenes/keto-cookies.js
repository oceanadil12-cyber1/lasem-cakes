/* Keto cookies: peanut butter cookies stack into a tower on a wooden board, cookie slices
   lean against it and peanuts scatter; gluten free and sugar free. */
LaSem3D.start(function (k) {
  var T = k.T, rand = k.rand;
  k.frame({ look: -0.55, h: 1.7, w: 2.7, elev: 0.66 });
  var by = k.board({ w: 2.7, d: 1.8 });

  var topTex = k.canvasTex(256, 256, function (g, w, h) {
    var grd = g.createRadialGradient(w / 2, h / 2, 10, w / 2, h / 2, w / 2);
    grd.addColorStop(0, '#d9a25c'); grd.addColorStop(1, '#b77b3a');
    g.fillStyle = grd; g.fillRect(0, 0, w, h);
    g.strokeStyle = 'rgba(120,70,25,.55)'; g.lineWidth = 7;
    for (var i = -3; i <= 3; i++) {
      g.beginPath(); g.moveTo(w / 2 + i * 34 - 90, 30); g.lineTo(w / 2 + i * 34 + 90, h - 30); g.stroke();
      g.beginPath(); g.moveTo(w / 2 + i * 34 + 90, 30); g.lineTo(w / 2 + i * 34 - 90, h - 30); g.stroke();
    }
    for (var j = 0; j < 300; j++) { g.fillStyle = 'rgba(255,230,180,.35)'; g.beginPath(); g.arc(rand(j) * w, rand(j + 1) * h, 1.6, 0, 7); g.fill(); }
  });
  var top = new T.MeshStandardMaterial({ map: topTex, roughness: 0.85 });
  var side = k.mat(0xbf8443, { roughness: 0.9 });

  k.cap(0.2, 'Peanut butter cookies');
  var fa = k.front(8);
  var tower = [Math.cos(fa) * -0.15, Math.sin(fa) * -0.15], y = by;
  for (var i = 0; i < 6; i++) {
    var c = k.shadow(new T.Mesh(new T.CylinderGeometry(0.24, 0.23, 0.07, 40), [side, top, side]));
    c.rotation.y = rand(i) * 6;
    k.drop(c, 0.4 + i * 0.45, 0.6, [tower[0] + (rand(i + 3) - 0.5) * 0.05, y + 0.035, tower[1] + (rand(i + 5) - 0.5) * 0.05], { spin: 2, height: 2.2, squash: 0.08 });
    y += 0.072;
  }
  k.cap(1.6, 'Gluten free');
  k.cap(2.6, 'Sugar free');

  k.cap(3.4, 'Chocolate chip and cream cheese slices');
  var slice = k.canvasTex(256, 128, function (g, w, h) {
    g.fillStyle = '#c99556'; g.fillRect(0, 0, w, h);
    for (var i = 0; i < 40; i++) { g.fillStyle = '#3a1d0e'; g.beginPath(); g.arc(rand(i) * w, rand(i + 1) * h, 4 + rand(i + 2) * 4, 0, 7); g.fill(); }
  });
  var sliceMat = new T.MeshStandardMaterial({ map: slice, roughness: 0.85 });
  var cheese = k.mat(0xf3e2bf, { roughness: 0.8 });
  [[0.55, 0.2, -0.5, sliceMat], [-0.55, 0.2, 0.5, cheese], [0.1, 0.62, 0.15, sliceMat]].forEach(function (s, i) {
    var bar = k.shadow(new T.Mesh(new T.BoxGeometry(0.42, 0.06, 0.2), [side, side, s[3], side, side, side]));
    bar.rotation.set(0, Math.atan2(s[1], s[0]) + s[2], 0);
    var px = Math.cos(fa) * s[1] + Math.cos(fa - Math.PI / 2) * s[0], pz = Math.sin(fa) * s[1] + Math.sin(fa - Math.PI / 2) * s[0];
    k.drop(bar, 3.5 + i * 0.3, 0.6, [px, by + 0.03, pz], { spin: 1.5 });
  });

  k.cap(4.8, 'Roasted peanuts');
  var nut = k.mat(0xd9a96a, { roughness: 0.7 });
  for (var p = 0; p < 18; p++) {
    var a = rand(p + 30) * Math.PI * 2, r = 0.45 + rand(p + 40) * 0.6;
    var n = k.shadow(new T.Mesh(new T.SphereGeometry(0.035, 12, 10), nut));
    n.scale.set(1.3, 0.75, 0.95);
    k.drop(n, 4.9 + p * 0.05, 0.5, [tower[0] + Math.cos(a) * r, by + 0.02, tower[1] + Math.sin(a) * r * 0.7], { spin: 5, height: 1.8 });
  }

  k.cap(5.9, 'Keto friendly');
  var pl = k.plaque({ text: 'Keto', sub: 'GLUTEN & SUGAR FREE', w: 0.5, h: 0.26, bg: '#f4efe2', ink: '#3d6b2b', subInk: '#3d6b2b' });
  var holder = k.group(); holder.add(pl);
  pl.rotation.x = -0.25;
  holder.rotation.y = k.facing(8);
  k.drop(holder, 6.0, 0.6, [Math.cos(fa) * 0.55 + Math.cos(fa + Math.PI / 2) * 0.45, by + 0.14, Math.sin(fa) * 0.55 + Math.sin(fa + Math.PI / 2) * 0.45], { spin: 0.5 });
  k.end(8);
});
