/* La Sem — 3D hero engine (three.js r128).
 *
 * Runs the "built live" animations: the home page cake and one scene per cake/bakery page.
 * A scene file calls LaSem3D.start(build) and builds its objects with the kit `k`
 * (sponge layers, icing, drips, rosettes, fruit, sugar roses, plaques, confetti…), placing
 * each one on a timeline: k.drop(obj, t0, dur, to), k.pop(…), k.rise(…) and so on.
 *
 * Everything is a function of time, so play, pause, replay and the finished still shown to
 * reduced-motion visitors all come from the same timeline. Nothing is a video file.
 */
(function () {
  'use strict';

  window.LaSem3D = { start: start };

  function start(build) {
    var hero = document.querySelector('[data-cake-hero]');
    if (!hero) return;
    if (!window.THREE || !webglAvailable()) { hero.classList.add('no-webgl'); return; }
    run(hero, build);
  }

  function run(hero, build) {
    var T = window.THREE;
    var canvas = hero.querySelector('canvas');
    var captionEl = hero.querySelector('[data-cake-caption]');
    var progressEl = hero.querySelector('[data-cake-progress]');
    var playBtn = hero.querySelector('[data-cake-play]');
    var replayBtn = hero.querySelector('[data-cake-replay]');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Renderer, lights, camera ---------- */

    var renderer = new T.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.outputEncoding = T.sRGBEncoding;
    renderer.toneMapping = T.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = T.PCFSoftShadowMap;
    renderer.setClearColor(0x000000, 0);

    var scene = new T.Scene();
    scene.environment = makeEnvironment();
    var camera = new T.PerspectiveCamera(30, 1, 0.1, 80);

    scene.add(new T.HemisphereLight(0xfff3e0, 0x3a2418, 0.55));
    var key = new T.SpotLight(0xffe8c8, 1.6, 0, Math.PI / 5.2, 0.55, 1);
    key.position.set(3.2, 6.5, 4.2);
    key.target.position.set(0, 0.5, 0);
    key.castShadow = true;
    key.shadow.mapSize.set(1024, 1024);
    key.shadow.bias = -0.0004;
    key.shadow.radius = 4;
    scene.add(key, key.target);
    var fill = new T.DirectionalLight(0xffd9b0, 0.35);
    fill.position.set(-4, 2.5, 3);
    scene.add(fill);
    var rim = new T.DirectionalLight(0xff9a4d, 0.9);
    rim.position.set(-2.5, 3, -4.5);
    scene.add(rim);

    // Only the shadow is drawn on the floor; the page background shows through.
    var floor = new T.Mesh(new T.CircleGeometry(5, 48), new T.ShadowMaterial({ opacity: 0.38 }));
    floor.rotation.x = -Math.PI / 2;
    floor.position.y = -0.9;
    floor.receiveShadow = true;
    scene.add(floor);

    var turntable = new T.Group();
    scene.add(turntable);

    var timeline = [], captions = [], bursts = [], tickers = [];
    var cfg = { look: 0.62, h: 3.5, w: 3.0, elev: 1, spinSpeed: 0.16, spinStart: 0.4, end: 15 };

    /* ---------- The kit handed to scenes ---------- */

    var texCache = {}, matCache = {};
    var k = {
      T: T,
      rand: rand,
      ease: ease, easeOut: easeOut, easeBack: easeBack,
      group: function () { return new T.Group(); },
      v: function (x, y, z) { return new T.Vector3(x, y, z); },

      // Scene settings: camera framing (look-at height, size to fit), floor, turntable speed, length.
      frame: function (o) { Object.assign(cfg, o); },
      floor: function (y) { floor.position.y = y; },
      rimColor: function (c) { rim.color.set(c).convertSRGBToLinear(); },
      end: function (t) { cfg.end = t; },
      cap: function (t, text) { captions.push({ t: t, text: text }); },
      spinAt: function (time) { return cfg.spinStart + time * cfg.spinSpeed; },
      // Rotation that makes something face the viewer at a given moment of the build.
      facing: function (time) { return -k.spinAt(time); },
      // Angle around the cake (for positions) that is at the front, nearest the viewer, at a given moment.
      front: function (time) { return k.spinAt(time) + Math.PI / 2; },

      add: function (obj) { turntable.add(obj); return obj; },
      shadow: shadowed,

      /* Materials (colours are sRGB, as designers write them) */
      mat: function (color, o) {
        var m = new T.MeshStandardMaterial(Object.assign({ color: color, roughness: 0.55 }, o || {}));
        m.color.convertSRGBToLinear();
        return m;
      },
      gloss: function (color, o) {
        var m = new T.MeshPhysicalMaterial(Object.assign({ color: color, roughness: 0.22, clearcoat: 1, clearcoatRoughness: 0.08 }, o || {}));
        m.color.convertSRGBToLinear();
        return m;
      },
      metal: function (color, rough) {
        var m = new T.MeshStandardMaterial({ color: color, roughness: rough == null ? 0.22 : rough, metalness: 1 });
        m.color.convertSRGBToLinear();
        return m;
      },
      glow: function (color) { return new T.MeshBasicMaterial({ color: color }); },
      M: {},
      canvasTex: canvasTex,

      /* Timeline */
      drop: function (obj, t0, dur, to, o) {
        o = o || {};
        add(obj);
        timeline.push({
          obj: obj, t0: t0, dur: dur, kind: 'drop', to: toVec(to), rot: obj.rotation.clone(),
          spin: o.spin || 0, flutter: !!o.flutter, squash: o.squash, height: o.height, base: obj.scale.clone()
        });
        return obj;
      },
      spread: function (obj, t0, dur) { return push(obj, t0, dur, 'spread'); },
      rise: function (obj, t0, dur) { return push(obj, t0, dur, 'rise'); },
      grow: function (obj, t0, dur) { return push(obj, t0, dur, 'grow'); },
      pipe: function (obj, t0, dur) { return push(obj, t0, dur, 'pipe'); },
      pop: function (obj, t0, dur) { return push(obj, t0, dur || 0.45, 'pop'); },
      move: function (obj, t0, dur, from, to, o) {
        add(obj);
        timeline.push({ obj: obj, t0: t0, dur: dur, kind: 'move', from: toVec(from), to: toVec(to), arc: (o && o.arc) || 0, base: obj.scale.clone(), rot: obj.rotation.clone(), spin: (o && o.spin) || 0 });
        return obj;
      },
      // Any custom animation: fn(p, obj, time) with p from 0 to 1.
      anim: function (obj, t0, dur, fn, o) {
        add(obj);
        timeline.push({ obj: obj, t0: t0, dur: dur, kind: 'fn', fn: fn, keep: o && o.keepVisible });
        return obj;
      },
      // Runs every frame (candle flames, bubbles…): fn(time).
      every: function (fn) { tickers.push(fn); },

      /* Effects */
      burst: function (o) { bursts.push(Object.assign({ kind: 'ring', n: 46, r: 1, color: 0xfff0d6, life: 0.9, size: 0.06, up: 1, speed: 1 }, o)); },
      confetti: function (o) { bursts.push(Object.assign({ kind: 'confetti', n: 140, life: 3.2, size: 0.07, spread: 2.6, from: 3.4, colors: ['#e2783a', '#f3c14b', '#f7f1e3', '#e45d8c', '#57b8c9'] }, o)); },
      sparkle: function (o) { bursts.push(Object.assign({ kind: 'sparkle', n: 36, life: 1.6, size: 0.05, color: 0xffe3a3, r: 1.1, y: 1.4 }, o)); },

      /* Standard pieces */
      stand: stand,
      plate: plate,
      board: board,
      sponge: sponge,
      cream: cream,
      coat: coat,
      top: topDisc,
      drips: drips,
      rosettes: rosettes,
      pearls: pearls,
      cherry: cherry,
      strawberry: strawberry,
      blueberry: blueberry,
      raspberry: raspberry,
      kiwi: kiwiSlice,
      rose: rose,
      leaf: leaf,
      plaque: plaque,
      sprinkles: sprinkles,
      candle: candle,
      G: {}
    };

    function push(obj, t0, dur, kind) {
      add(obj);
      timeline.push({ obj: obj, t0: t0, dur: dur, kind: kind, base: obj.scale.clone() });
      return obj;
    }
    function add(obj) { if (!obj.parent) turntable.add(obj); }
    function toVec(v) { return Array.isArray(v) ? new T.Vector3(v[0], v[1], v[2]) : v; }

    /* ---------- Shared materials and geometry ---------- */

    k.M.porcelain = k.gloss(0xfbf7f1, { roughness: 0.18 });
    k.M.cream = k.mat(0xfff6e6, { roughness: 0.62 });
    k.M.frosting = k.mat(0xfffaf2, { roughness: 0.48, bumpMap: streakTexture(), bumpScale: 0.006 });
    k.M.rosette = k.mat(0xfffaf3);
    k.M.ganache = k.gloss(0x1c0b05, { roughness: 0.32, clearcoat: 0.6, envMapIntensity: 0.35 });
    k.M.cherry = k.gloss(0x8a0716, { roughness: 0.16 });
    k.M.stem = k.mat(0x5d6a2b, { roughness: 0.7 });
    k.M.strawberry = new T.MeshPhysicalMaterial({ map: strawberryTexture(), roughness: 0.32, clearcoat: 0.8, clearcoatRoughness: 0.15 });
    k.M.leaf = k.mat(0x3d8a35, { roughness: 0.5, side: T.DoubleSide });
    k.M.blueberry = k.mat(0x2c3866, { roughness: 0.45 });
    k.M.raspberry = k.gloss(0xb3123a, { roughness: 0.35, clearcoat: 0.6 });
    k.M.gold = k.metal(0xe8c372);
    k.M.silver = k.metal(0xd9dde3, 0.18);
    k.M.kiwiFace = new T.MeshPhysicalMaterial({ map: kiwiTexture(), roughness: 0.28, clearcoat: 0.7 });
    k.M.kiwiSkin = k.mat(0x6b5131, { roughness: 0.9 });
    k.M.wood = new T.MeshStandardMaterial({ map: woodTexture(), roughness: 0.7 });

    k.G.mint = (function () {
      var s = new T.Shape();
      s.moveTo(0, -0.09);
      s.bezierCurveTo(0.07, -0.05, 0.07, 0.05, 0, 0.1);
      s.bezierCurveTo(-0.07, 0.05, -0.07, -0.05, 0, -0.09);
      var geo = new T.ShapeGeometry(s, 12);
      var pos = geo.attributes.position;
      for (var i = 0; i < pos.count; i++) pos.setZ(i, Math.pow(pos.getX(i) * 10, 2) * 0.012);
      geo.computeVertexNormals();
      return geo;
    })();
    k.G.rosette = rosetteGeo(0.115, 0.17, 8);
    k.G.cherry = (function () {
      var prof = [];
      for (var i = 0; i <= 16; i++) {
        var a = -Math.PI / 2 + (i / 16) * Math.PI;
        prof.push(new T.Vector2(Math.max(Math.cos(a) * 0.085, 0.001), Math.sin(a) * 0.08 - (i > 13 ? (i - 13) * 0.006 : 0)));
      }
      return new T.LatheGeometry(prof, 40);
    })();
    k.G.strawberry = (function () {
      var prof = [new T.Vector2(0.001, -0.16)];
      for (var i = 1; i <= 14; i++) {
        var q = i / 14;
        prof.push(new T.Vector2(0.1 * Math.sin(Math.pow(q, 0.75) * Math.PI * 0.62) / Math.sin(Math.PI * 0.62) * (q > 0.92 ? 0.85 : 1), -0.16 + q * 0.2));
      }
      prof.push(new T.Vector2(0.001, 0.045));
      return new T.LatheGeometry(prof, 40);
    })();
    k.G.petal = new T.SphereGeometry(0.07, 14, 8, 0, Math.PI * 0.75, 0, Math.PI * 0.42);
    k.G.rosetteGeo = rosetteGeo;
    k.G.roundedRect = roundedRectShape;

    /* ---------- Builders ---------- */

    // Porcelain cake stand; the cake board's top is at y = 0.06.
    function stand(o) {
      o = o || {};
      var m = o.mat || k.M.porcelain;
      var r = o.r || 1.34;
      var b = new T.Mesh(new T.CylinderGeometry(r, r + 0.04, 0.06, 96), m);
      b.position.y = 0.03;
      var pts = [[0, -0.9], [0.78, -0.9], [0.8, -0.86], [0.7, -0.8], [0.3, -0.7], [0.16, -0.5], [0.13, -0.2], [0.2, -0.06], [0.5, -0.02], [0, -0.02]]
        .map(function (p) { return new T.Vector2(p[0], p[1]); });
      var foot = new T.Mesh(new T.LatheGeometry(pts, 80), m);
      turntable.add(shadowed(b), shadowed(foot));
      return 0.06;
    }
    // A flat round plate sitting on the floor plane.
    function plate(o) {
      o = o || {};
      var r = o.r || 1.3, y = o.y == null ? -0.9 : o.y;
      var prof = [[0, 0], [r * 0.7, 0], [r * 0.72, 0.012], [r * 0.95, 0.05], [r, 0.075], [r * 0.98, 0.085], [r * 0.93, 0.065], [r * 0.7, 0.03], [0, 0.03]]
        .map(function (p) { return new T.Vector2(p[0], p[1]); });
      var m = new T.Mesh(new T.LatheGeometry(prof, 96), o.mat || k.M.porcelain);
      m.position.y = y;
      turntable.add(shadowed(m));
      return y + 0.03;
    }
    // Wooden serving board (rectangle with rounded corners).
    function board(o) {
      o = o || {};
      var w = o.w || 2.6, d = o.d || 1.7, h = o.h || 0.09, y = o.y == null ? -0.9 : o.y;
      var geo = new T.ExtrudeGeometry(roundedRectShape(w, d, 0.12), { depth: h, bevelEnabled: true, bevelThickness: 0.02, bevelSize: 0.02, bevelSegments: 2 });
      geo.rotateX(-Math.PI / 2);
      var m = new T.Mesh(geo, o.mat || k.M.wood);
      m.position.y = y;
      turntable.add(shadowed(m));
      return y + h + 0.02;
    }

    var spongeTex = {};
    function spongeMats(flavour) {
      if (spongeTex[flavour]) return spongeTex[flavour];
      var c = {
        vanilla: ['#c98a3e', '#e7bf74', '#ecc983', '#f0d396', 'rgba(150,95,35,0.35)', 'rgba(255,240,200,0.45)'],
        chocolate: ['#2e170d', '#5a3020', '#62351f', '#4d2a19', 'rgba(20,8,3,0.45)', 'rgba(140,90,60,0.35)'],
        red: ['#5c0f15', '#9e1b25', '#a7202b', '#8f1a23', 'rgba(60,5,10,0.45)', 'rgba(220,110,110,0.3)'],
        almond: ['#cfa66a', '#ecd3a0', '#f0dbab', '#f3e2bb', 'rgba(150,110,50,0.3)', 'rgba(255,248,225,0.5)'],
        pound: ['#b8762f', '#f0cf7e', '#f3d68b', '#f5df9f', 'rgba(170,110,35,0.3)', 'rgba(255,245,210,0.45)']
      }[flavour] || null;
      if (!c) return spongeMats('vanilla');
      var side = canvasTex(1024, 128, function (g, w, h) {
        var grd = g.createLinearGradient(0, 0, 0, h);
        grd.addColorStop(0, c[0]); grd.addColorStop(0.1, c[1]); grd.addColorStop(0.9, c[2]); grd.addColorStop(1, c[0]);
        g.fillStyle = grd; g.fillRect(0, 0, w, h);
        crumbs(g, w, h, 2600, c[4], c[5]);
      });
      side.wrapS = T.RepeatWrapping;
      var top = canvasTex(256, 256, function (g, w, h) { g.fillStyle = c[3]; g.fillRect(0, 0, w, h); crumbs(g, w, h, 900, c[4], c[5]); });
      spongeTex[flavour] = [
        new T.MeshStandardMaterial({ map: side, roughness: 0.92 }),
        new T.MeshStandardMaterial({ map: top, roughness: 0.95 }),
        new T.MeshStandardMaterial({ map: top, roughness: 0.95 })
      ];
      return spongeTex[flavour];
    }

    // A sponge layer that drops in with a puff of flour. Returns the layer's top height.
    function sponge(o) {
      var r = o.r || 1, h = o.h || 0.32;
      var geo = new T.CylinderGeometry(r, r, h, 96, 1);
      geo.translate(0, h / 2, 0);
      var m = shadowed(new T.Mesh(geo, spongeMats(o.flavour || 'vanilla')));
      m.rotation.y = rand(o.y * 10 + r) * 6;
      var dur = o.dur || 0.85;
      k.drop(m, o.t0, dur, [o.x || 0, o.y, o.z || 0], { squash: 0.09, height: o.height || 3.2 });
      if (o.burst !== false) bursts.push({ kind: 'ring', t: o.t0 + dur * 0.62, y: o.y, r: r, n: 46, color: 0xfff0d6, life: 0.9, size: 0.06, up: 1, speed: 1, x: o.x || 0, z: o.z || 0 });
      return o.y + h;
    }
    // One covered tier: a sponge drops in, then icing or fondant rises over it. Returns the top height.
    function tier(o) {
      var top = sponge({ r: o.r, h: o.h, y: o.y, t0: o.t0, flavour: o.flavour, x: o.x, z: o.z, height: o.height });
      coat({ r: o.r + 0.035, y0: o.y, y1: top + 0.025, t0: o.t0 + (o.gap || 0.8), dur: o.coatDur || 0.8, mat: o.mat, x: o.x, z: o.z });
      return top + 0.025;
    }
    k.tier = tier;

    // A texture that is painted stroke by stroke (hand-painted designs, piped writing).
    // base(g, w, h) paints the background; each op(g, w, h) adds one stroke. draw(n) shows the first n.
    k.painter = function (w, h, base, ops, o) {
      var c = document.createElement('canvas');
      c.width = w; c.height = h;
      var g = c.getContext('2d'), tex = new T.CanvasTexture(c), shown = -1, want = 0;
      if (!(o && o.linear)) tex.encoding = T.sRGBEncoding;
      tex.anisotropy = renderer.capabilities.getMaxAnisotropy();
      if (o && o.wrap) tex.wrapS = T.RepeatWrapping;
      function draw(n) {
        want = n = Math.max(0, Math.min(ops.length, n));
        if (n === shown) return;
        shown = n;
        g.clearRect(0, 0, w, h);
        base(g, w, h);
        for (var i = 0; i < n; i++) ops[i](g, w, h);
        tex.needsUpdate = true;
      }
      draw(0);
      // Script fonts may arrive after the first paint: repaint once they are ready.
      if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { shown = -1; draw(want); render(); });
      return { tex: tex, ops: ops, draw: draw };
    };
    // Paint a painter's strokes on over a stretch of the timeline.
    k.reveal = function (p, t0, dur) {
      var d = new T.Group();
      k.anim(d, t0, dur, function (q) { p.draw(Math.round(p.ops.length * q)); }, { keepVisible: true });
    };

    function cream(o) {
      var r = o.r || 1, h = o.h || 0.07;
      var geo = new T.CylinderGeometry(r - 0.015, r - 0.04, h, 96, 1);
      geo.translate(0, h / 2, 0);
      var m = shadowed(new T.Mesh(geo, o.mat || k.M.cream));
      m.position.set(o.x || 0, o.y, o.z || 0);
      k.spread(m, o.t0, o.dur || 0.55);
      return o.y + h;
    }
    // Icing that rises up the sides from y0 to y1.
    function coat(o) {
      var r = o.r || 1.05, h = o.y1 - o.y0;
      var geo = new T.CylinderGeometry(r, r, h, 96, 1);
      geo.translate(0, h / 2, 0);
      var m = shadowed(new T.Mesh(geo, o.mat || k.M.frosting));
      m.position.set(o.x || 0, o.y0, o.z || 0);
      k.rise(m, o.t0, o.dur || 1.05);
      return m;
    }
    // A thin disc that spreads over a top (ganache, glaze, fondant).
    function topDisc(o) {
      var r = o.r || 1.055, h = o.h || 0.03;
      var m = shadowed(new T.Mesh(new T.CylinderGeometry(r, r, h, 96), o.mat || k.M.ganache));
      m.position.set(o.x || 0, o.y + h / 2, o.z || 0);
      k.spread(m, o.t0, o.dur || 0.45);
      return o.y + h;
    }
    function drips(o) {
      var n = o.n || 26, mat = o.mat || k.M.ganache;
      for (var d = 0; d < n; d++) {
        var a = (d / n) * Math.PI * 2 + rand(d + (o.seed || 0)) * 0.12;
        var len = (o.min || 0.12) + rand(d + 40 + (o.seed || 0)) * ((o.max || 0.46) - (o.min || 0.12));
        var grp = new T.Group();
        var th = o.thick || 0.03;
        var body = new T.Mesh(new T.CylinderGeometry(th, th * 0.86, len, 12), mat);
        body.position.y = -len / 2;
        var tip = new T.Mesh(new T.SphereGeometry(th * 1.2, 14, 10), mat);
        tip.scale.y = 1.25;
        tip.position.y = -len;
        grp.add(body, tip);
        grp.position.set((o.x || 0) + Math.cos(a) * o.r, o.y, (o.z || 0) + Math.sin(a) * o.r);
        k.grow(grp, o.t0 + rand(d + 80) * 0.5, 0.75);
      }
    }
    // Ring of piped rosettes; returns the top point of each.
    function rosettes(o) {
      var out = [], n = o.n || 12, s = o.scale || 1;
      for (var i = 0; i < n; i++) {
        var a = (i / n) * Math.PI * 2 + (o.offset == null ? 0.2 : o.offset);
        var r = shadowed(new T.Mesh(o.geo || k.G.rosette, o.mat || k.M.rosette));
        r.scale.setScalar(s);
        r.position.set((o.x || 0) + Math.cos(a) * o.r, o.y, (o.z || 0) + Math.sin(a) * o.r);
        r.rotation.y = -a;
        k.pipe(r, o.t0 + i * (o.step || 0.075), 0.42);
        out.push(new T.Vector3(r.position.x, o.y + 0.17 * s, r.position.z));
      }
      return out;
    }
    // Ring of pearls that pop in one after another (piped borders, sugar pearls).
    function pearls(o) {
      var n = o.n || 40, size = o.size || 0.035, geo = new T.SphereGeometry(size, 12, 10);
      for (var i = 0; i < n; i++) {
        var a = (i / n) * Math.PI * 2;
        var p = shadowed(new T.Mesh(geo, o.mat || k.M.rosette));
        p.position.set((o.x || 0) + Math.cos(a) * o.r, o.y + size * 0.6, (o.z || 0) + Math.sin(a) * o.r);
        k.pop(p, o.t0 + i * (o.step || 0.02), 0.3);
      }
    }
    function cherry() {
      var grp = new T.Group();
      grp.add(shadowed(new T.Mesh(k.G.cherry, k.M.cherry)));
      var curve = new T.CatmullRomCurve3([k.v(0, 0.07, 0), k.v(0.015, 0.14, 0.005), k.v(0.05, 0.21, 0.01), k.v(0.1, 0.25, 0.015)]);
      grp.add(new T.Mesh(new T.TubeGeometry(curve, 16, 0.007, 6, false), k.M.stem));
      return grp;
    }
    function strawberry(scale) {
      var grp = new T.Group();
      grp.add(shadowed(new T.Mesh(k.G.strawberry, k.M.strawberry)));
      for (var i = 0; i < 6; i++) {
        var lf = new T.Mesh(k.G.mint, k.M.leaf);
        lf.scale.set(0.45, 0.6, 0.45);
        lf.position.set(0, 0.045, 0);
        lf.rotation.set(-Math.PI / 2 + 0.35, 0, (i / 6) * Math.PI * 2);
        lf.translateY(0.04);
        grp.add(lf);
      }
      grp.scale.setScalar(scale || 1);
      return grp;
    }
    function blueberry() {
      var m = shadowed(new T.Mesh(new T.SphereGeometry(0.048, 20, 16), k.M.blueberry));
      m.scale.set(1, 0.88, 1);
      return m;
    }
    function raspberry() {
      var grp = new T.Group(), g = new T.SphereGeometry(0.022, 8, 6);
      for (var i = 0; i < 26; i++) {
        var phi = Math.acos(1 - (i + 0.5) / 26 * 1.6), th = i * 2.4;
        var d = new T.Mesh(g, k.M.raspberry);
        d.position.set(Math.sin(phi) * Math.cos(th) * 0.055, Math.cos(phi) * 0.06, Math.sin(phi) * Math.sin(th) * 0.055);
        grp.add(d);
      }
      return grp;
    }
    function kiwiSlice(r) {
      return shadowed(new T.Mesh(new T.CylinderGeometry(r, r, 0.028, 40), [k.M.kiwiSkin, k.M.kiwiFace, k.M.kiwiFace]));
    }
    // A sugar rose: a bud wrapped by three rings of opening petals.
    function rose(o) {
      o = o || {};
      var mat = o.mat || k.gloss(o.color || 0xf3c6cf, { roughness: 0.45, clearcoat: 0.3, side: T.DoubleSide });
      var grp = new T.Group();
      var bud = new T.Mesh(new T.SphereGeometry(0.045, 14, 10), mat);
      bud.scale.set(1, 1.25, 1);
      bud.position.y = 0.045;
      grp.add(bud);
      [[5, 0.03, 0.15, 0.85], [6, 0.05, 0.6, 1], [7, 0.07, 1.05, 1.15]].forEach(function (ring, ri) {
        for (var i = 0; i < ring[0]; i++) {
          var a = (i / ring[0]) * Math.PI * 2 + ri * 0.5;
          var p = new T.Mesh(k.G.petal, mat);
          p.scale.setScalar(ring[3]);
          p.position.set(Math.cos(a) * ring[1], 0.02, Math.sin(a) * ring[1]);
          p.rotation.set(0, -a + Math.PI * 0.6, 0);
          p.rotateX(-ring[2] * 0.6);
          grp.add(p);
        }
      });
      grp.scale.setScalar(o.scale || 1);
      grp.traverse(function (c) { c.castShadow = true; });
      return grp;
    }
    function leaf(color, scale) {
      var m = new T.Mesh(k.G.mint, color ? k.mat(color, { side: T.DoubleSide }) : k.M.leaf);
      m.scale.setScalar(scale || 1);
      return m;
    }
    // A flat plaque with text drawn on it (chocolate or sugar). Faces +z.
    function plaque(o) {
      var w = o.w || 0.5, h = o.h || 0.2;
      var geo = new T.ExtrudeGeometry(roundedRectShape(w, h, Math.min(w, h) * 0.3), { depth: 0.025, bevelEnabled: true, bevelThickness: 0.006, bevelSize: 0.006, bevelSegments: 2, curveSegments: 10 });
      var uv = geo.attributes.uv, pos = geo.attributes.position;
      for (var i = 0; i < uv.count; i++) uv.setXY(i, (pos.getX(i) + w / 2 + 0.006) / (w + 0.012), (pos.getY(i) + h / 2 + 0.006) / (h + 0.012));
      var px = 512, py = Math.round(512 * h / w);
      var draw = function (g, cw, ch) {
        g.fillStyle = o.bg || '#f6ead6'; g.fillRect(0, 0, cw, ch);
        if (o.border) { g.strokeStyle = o.border; g.lineWidth = 8; g.strokeRect(14, 14, cw - 28, ch - 28); }
        g.textAlign = 'center'; g.textBaseline = 'middle';
        g.fillStyle = o.ink || '#4a2414';
        var size = o.size || Math.round(ch * (o.sub ? 0.45 : 0.55));
        g.font = (o.font === 'serif' ? '600 ' + size + 'px "Fraunces", Georgia, serif' : size + 'px "Kaushan Script", "Brush Script MT", cursive');
        g.fillText(o.text, cw / 2, ch * (o.sub ? 0.42 : 0.52));
        if (o.sub) {
          g.font = '700 ' + Math.round(ch * 0.13) + 'px "Manrope", Arial, sans-serif';
          g.fillStyle = o.subInk || '#b8642a';
          g.fillText(o.sub, cw / 2, ch * 0.8);
        }
      };
      var tex = canvasTex(px, py, draw);
      if (document.fonts && document.fonts.load) {
        Promise.all([document.fonts.load('80px "Kaushan Script"'), document.fonts.load('600 40px "Fraunces"')]).then(function () {
          draw(tex.image.getContext('2d'), px, py);
          tex.needsUpdate = true;
          render();
        });
      }
      var side = o.sideMat || k.mat(o.side || o.bg || '#f4e7d0', { roughness: 0.4 });
      var m = shadowed(new T.Mesh(geo, [new T.MeshStandardMaterial({ map: tex, roughness: 0.38 }), side]));
      geo.translate(0, 0, -0.0125);
      return m;
    }
    // Sprinkles that rain onto a disc of radius r at height y.
    function sprinkles(o) {
      var colors = o.colors || ['#e45d8c', '#f3c14b', '#57b8c9', '#7cc36a', '#f7f1e3', '#e2783a'];
      var mats = colors.map(function (c) { return k.mat(c, { roughness: 0.4 }); });
      var geo = new T.CylinderGeometry(0.009, 0.009, 0.05, 6);
      for (var i = 0; i < (o.n || 50); i++) {
        var a = rand(i + 300) * Math.PI * 2, rr = Math.sqrt(rand(i + 400)) * o.r;
        var s = new T.Mesh(geo, mats[i % mats.length]);
        s.rotation.set(Math.PI / 2, rand(i) * 6, 0);
        k.drop(s, o.t0 + rand(i + 500) * (o.span || 0.8), 0.5, [(o.x || 0) + Math.cos(a) * rr, (o.yAt ? o.yAt(rr) : o.y) + 0.01, (o.z || 0) + Math.sin(a) * rr], { spin: 5, height: 2 });
      }
    }
    // A candle with a flickering flame.
    function candle(o) {
      o = o || {};
      var grp = new T.Group(), h = o.h || 0.32;
      var stick = shadowed(new T.Mesh(new T.CylinderGeometry(0.022, 0.022, h, 12), k.mat(o.color || '#f7f1e3', { roughness: 0.4 })));
      stick.position.y = h / 2;
      var stripe = new T.Mesh(new T.TorusGeometry(0.023, 0.006, 6, 16), k.mat(o.stripe || '#e45d8c'));
      stripe.rotation.x = Math.PI / 2;
      grp.add(stick);
      for (var i = 0; i < 4; i++) { var s = stripe.clone(); s.position.y = h * (0.2 + i * 0.2); grp.add(s); }
      var flame = new T.Mesh(new T.SphereGeometry(0.03, 12, 10), k.glow(0xffc35a));
      flame.scale.set(0.8, 1.9, 0.8);
      flame.position.y = h + 0.06;
      var core = new T.Mesh(new T.SphereGeometry(0.014, 10, 8), k.glow(0xfff6d8));
      core.scale.set(1, 1.6, 1);
      core.position.y = h + 0.045;
      grp.add(flame, core);
      grp.userData.flame = flame;
      k.every(function (time) {
        var f = 1 + Math.sin(time * 23 + (o.seed || 0)) * 0.08 + Math.sin(time * 37 + (o.seed || 0) * 2) * 0.06;
        flame.scale.set(0.8 * f, 1.9 * (2 - f), 0.8 * f);
      });
      return grp;
    }

    /* ---------- Build the scene ---------- */

    build(k);

    /* ---------- Particles ---------- */

    var dotTex = canvasTex(64, 64, function (g, w, h) {
      var grd = g.createRadialGradient(w / 2, h / 2, 0, w / 2, h / 2, w / 2);
      grd.addColorStop(0, 'rgba(255,255,255,1)'); grd.addColorStop(1, 'rgba(255,255,255,0)');
      g.fillStyle = grd; g.fillRect(0, 0, w, h);
    });
    bursts.forEach(function (b, bi) {
      var geo = new T.BufferGeometry();
      geo.setAttribute('position', new T.BufferAttribute(new Float32Array(b.n * 3), 3));
      var matOpts = { size: b.size, transparent: true, depthWrite: false, opacity: 0 };
      if (b.kind === 'confetti') {
        var cols = new Float32Array(b.n * 3), c = new T.Color();
        for (var i = 0; i < b.n; i++) { c.set(b.colors[i % b.colors.length]).convertSRGBToLinear(); cols.set([c.r, c.g, c.b], i * 3); }
        geo.setAttribute('color', new T.BufferAttribute(cols, 3));
        matOpts.vertexColors = true;
      } else {
        matOpts.color = b.color;
        matOpts.map = dotTex;
        if (b.kind === 'sparkle') matOpts.blending = T.AdditiveBlending;
      }
      b.points = new T.Points(geo, new T.PointsMaterial(matOpts));
      b.points.frustumCulled = false;
      turntable.add(b.points);
      b.seeds = [];
      for (var j = 0; j < b.n; j++) b.seeds.push([rand(j + bi * 97), rand(j + 31 + bi * 13), rand(j + 71 + bi * 7), rand(j + 113 + bi)]);
    });
    function poseBurst(b, time) {
      var dt = time - b.t;
      if (dt < 0 || dt > b.life) { b.points.visible = false; return; }
      b.points.visible = true;
      var q = dt / b.life;
      var arr = b.points.geometry.attributes.position.array;
      for (var i = 0; i < b.seeds.length; i++) {
        var s = b.seeds[i], x, y, z;
        if (b.kind === 'ring') {
          var a = s[0] * Math.PI * 2, r = b.r + 0.02 + (0.6 + s[1] * 0.9) * dt * 0.7 * b.speed;
          x = Math.cos(a) * r; z = Math.sin(a) * r;
          y = b.y + 0.02 + (0.4 + s[2] * 0.9) * dt * b.up - 0.9 * dt * dt;
        } else if (b.kind === 'confetti') {
          var a2 = s[0] * Math.PI * 2, rr = Math.sqrt(s[1]) * b.spread;
          x = Math.cos(a2) * rr + Math.sin(dt * 3 + s[2] * 9) * 0.12;
          z = Math.sin(a2) * rr + Math.cos(dt * 2.5 + s[3] * 9) * 0.12;
          y = b.from + s[2] * 1.2 - dt * (0.9 + s[3] * 0.6);
        } else {
          var a3 = s[0] * Math.PI * 2, r3 = b.r * (0.3 + s[1] * 0.9);
          x = Math.cos(a3) * r3; z = Math.sin(a3) * r3;
          y = b.y + s[2] * 1.2 + dt * 0.25;
        }
        arr[i * 3] = x + (b.x || 0); arr[i * 3 + 1] = y; arr[i * 3 + 2] = z + (b.z || 0);
      }
      b.points.geometry.attributes.position.needsUpdate = true;
      b.points.material.opacity = b.kind === 'sparkle' ? Math.sin(q * Math.PI) * 0.9 : b.kind === 'confetti' ? Math.min(1, (1 - q) * 3) : 0.75 * (1 - q);
      if (b.kind === 'sparkle') b.points.material.size = b.size * (0.6 + 0.4 * Math.sin(time * 18));
    }

    /* ---------- Playback ---------- */

    var END = cfg.end;
    var t = reduceMotion ? END : 0;
    var playing = !reduceMotion;
    var spin = k.spinAt(t), spinVel = 0;
    var visible = true, last = performance.now(), lastCaption = -1, raf = 0;

    resize();
    pose(t);
    render();
    hero.classList.add('is-ready');
    updateUi(true);
    raf = requestAnimationFrame(frame);

    function frame(now) {
      var dt = Math.min((now - last) / 1000, 0.1);
      last = now;
      if (playing) {
        t = Math.min(t + dt, END);
        if (t >= END) playing = false;
      }
      if (!reduceMotion) spin += dt * cfg.spinSpeed;
      spin += spinVel;
      spinVel *= 0.92;
      pose(t);
      render();
      updateUi();
      raf = visible ? requestAnimationFrame(frame) : 0;
    }
    function wake() { if (!raf && visible) { last = performance.now(); raf = requestAnimationFrame(frame); } }

    // For previews and screenshots: LaSemCake.seek(9) jumps to 9 s and pauses there.
    window.LaSemCake = {
      seek: function (s) { t = Math.max(0, Math.min(s, END)); spin = k.spinAt(t); playing = false; pose(t); render(); updateUi(true); },
      play: function () { playing = true; wake(); },
      end: END
    };

    if (playBtn) playBtn.addEventListener('click', function () {
      if (t >= END) { t = 0; spin = k.spinAt(0); }
      playing = !playing;
      updateUi(true);
      wake();
    });
    if (replayBtn) replayBtn.addEventListener('click', function () {
      t = 0; spin = k.spinAt(0); playing = true;
      updateUi(true);
      wake();
    });

    // Drag (or swipe sideways) to turn the turntable. Vertical swipes still scroll the page.
    var dragX = null;
    canvas.addEventListener('pointerdown', function (e) { dragX = e.clientX; canvas.setPointerCapture(e.pointerId); });
    canvas.addEventListener('pointermove', function (e) {
      if (dragX === null) return;
      spinVel = (e.clientX - dragX) * 0.004;
      dragX = e.clientX;
      wake();
    });
    ['pointerup', 'pointercancel'].forEach(function (n) { canvas.addEventListener(n, function () { dragX = null; }); });

    // Stop drawing when the hero is off screen or the tab is hidden.
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting && !document.hidden; wake(); }, { threshold: 0.02 }).observe(hero);
    }
    document.addEventListener('visibilitychange', function () { visible = !document.hidden; wake(); });
    window.addEventListener('resize', function () { resize(); render(); });

    function updateUi(force) {
      if (progressEl) progressEl.style.transform = 'scaleX(' + (t / END).toFixed(4) + ')';
      var idx = -1;
      for (var i = 0; i < captions.length; i++) if (t >= captions[i].t) idx = i;
      if (captionEl && (idx !== lastCaption || force)) {
        lastCaption = idx;
        captionEl.classList.remove('is-in');
        void captionEl.offsetWidth;
        captionEl.textContent = idx >= 0 ? captions[idx].text : '';
        if (idx >= 0) captionEl.classList.add('is-in');
      }
      if (playBtn) {
        var label = playing ? 'Pause' : (t >= END ? 'Play again' : 'Play');
        if (playBtn.getAttribute('aria-label') !== label) {
          playBtn.setAttribute('aria-label', label);
          playBtn.dataset.state = playing ? 'playing' : 'paused';
        }
      }
    }

    function resize() {
      var w = canvas.clientWidth, h = canvas.clientHeight;
      if (!w || !h) return;
      renderer.setSize(w, h, false);
      camera.aspect = w / h;
      // Wide screens: the subject sits on the right, next to the headline, and fits in the band between
      // the fixed header and the player controls, so tall cakes never run under the menu on short screens.
      var wide = window.matchMedia('(min-width: 900px)').matches;
      var tanHalf = Math.tan(T.MathUtils.degToRad(camera.fov / 2));
      var frac = 0.8, shift = 0;
      if (wide) {
        var header = document.querySelector('.site-header');
        var player = hero.querySelector('.player');
        var top = (header ? header.offsetHeight : 0) + 20;
        var bottom = player ? Math.min(h, player.offsetTop) - 12 : h - 24;
        if (bottom - top < h * 0.45) { top = h * 0.12; bottom = h * 0.88; }
        frac = (bottom - top) / h * 0.94;
        shift = h / 2 - (top + bottom) / 2 + h * 0.04;
        camera.setViewOffset(w, h, -w * 0.21, shift, w, h);
      } else {
        camera.setViewOffset(w, h, 0, 0, w, h);
      }
      var byHeight = cfg.h / (2 * tanHalf * frac);
      var byWidth = cfg.w / (2 * tanHalf * camera.aspect * (wide ? 0.5 : 0.92));
      camera.userData.dist = Math.max(byHeight, byWidth) / 5.2;
      camera.updateProjectionMatrix();
    }

    function render() {
      // A slow push-in while the build plays, then it holds.
      var q = ease(Math.min(t / END, 1));
      var fit = camera.userData.dist || 1;
      camera.position.set(0, (2.05 + 0.55 * q) * fit * cfg.elev, (5.6 - 0.4 * q) * fit);
      camera.lookAt(0, cfg.look, 0);
      turntable.rotation.y = spin;
      renderer.render(scene, camera);
    }

    /* ---------- Timeline ---------- */

    function pose(time) {
      for (var i = 0; i < timeline.length; i++) {
        var it = timeline[i], b = it.base;
        var p = (time - it.t0) / it.dur;
        if (p <= 0) { it.obj.visible = !!it.keep; if (it.keep) it.fn(0, it.obj, time); continue; }
        it.obj.visible = true;
        p = Math.min(p, 1);
        switch (it.kind) {
          case 'drop': poseDrop(it, p); break;
          case 'spread': var e = 0.15 + 0.85 * easeBack(p); it.obj.scale.set(b.x * e, b.y * Math.max(p, 0.01), b.z * e); break;
          case 'rise': it.obj.scale.set(b.x, b.y * Math.max(ease(p), 0.001), b.z); break;
          case 'grow': it.obj.scale.set(b.x, b.y * Math.max(easeOut(p), 0.001), b.z); break;
          case 'pipe': var s = easeBack(p); it.obj.scale.set(b.x * s, b.y * Math.max(easeOut(p), 0.01), b.z * s); break;
          case 'pop': var s2 = Math.max(easeBack(p), 0.001); it.obj.scale.set(b.x * s2, b.y * s2, b.z * s2); break;
          case 'move':
            var q = ease(p);
            it.obj.position.lerpVectors(it.from, it.to, q);
            it.obj.position.y += Math.sin(q * Math.PI) * it.arc;
            it.obj.rotation.set(it.rot.x, it.rot.y + (1 - q) * it.spin, it.rot.z);
            break;
          case 'fn': it.fn(p, it.obj, time); break;
        }
      }
      for (var j = 0; j < bursts.length; j++) poseBurst(bursts[j], time);
      for (var n = 0; n < tickers.length; n++) tickers[n](time);
    }

    function poseDrop(it, p) {
      var fall = 0.62, from = it.to.y + (it.height || 2.6), y, squash = 0;
      if (p < fall) {
        var q = p / fall;
        y = from + (it.to.y - from) * q * q;
      } else {
        var q2 = (p - fall) / (1 - fall);
        y = it.to.y + Math.abs(Math.sin(q2 * Math.PI * 2)) * (1 - q2) * (q2 < 0.5 ? 0.09 : 0.03);
        squash = q2 < 0.18 ? Math.sin((q2 / 0.18) * Math.PI) : 0;
      }
      it.obj.position.set(it.to.x, y, it.to.z);
      var left = 1 - Math.min(p / fall, 1);
      it.obj.rotation.set(it.rot.x + left * it.spin * 0.5, it.rot.y + left * it.spin * 2, it.rot.z + left * it.spin * 0.3);
      if (it.flutter) {
        it.obj.position.x += Math.sin(p * 18) * 0.08 * left;
        it.obj.position.z += Math.cos(p * 14) * 0.06 * left;
      }
      var sq = squash * (it.squash || 0.06), b = it.base;
      it.obj.scale.set(b.x * (1 + sq), b.y * (1 - sq * 1.6), b.z * (1 + sq));
    }

    /* ---------- Textures ---------- */

    function canvasTex(w, h, draw, color) {
      var c = document.createElement('canvas');
      c.width = w; c.height = h;
      draw(c.getContext('2d'), w, h);
      var tex = new T.CanvasTexture(c);
      if (color !== false) tex.encoding = T.sRGBEncoding;
      tex.anisotropy = renderer.capabilities.getMaxAnisotropy();
      return tex;
    }
    function crumbs(g, w, h, n, dark, light) {
      for (var i = 0; i < n; i++) {
        var x = rand(i * 3.1) * w, y = rand(i * 7.7 + 1) * h, r = 0.6 + rand(i * 1.3 + 2) * 2.2;
        g.fillStyle = rand(i + 5) > 0.55 ? (dark || 'rgba(150,95,35,0.35)') : (light || 'rgba(255,240,200,0.45)');
        g.beginPath(); g.arc(x, y, r, 0, 7); g.fill();
      }
    }
    function streakTexture() {
      return canvasTex(512, 256, function (g, w, h) {
        g.fillStyle = '#808080'; g.fillRect(0, 0, w, h);
        for (var i = 0; i < 70; i++) {
          g.fillStyle = rand(i + 901) > 0.5 ? 'rgba(255,255,255,0.18)' : 'rgba(0,0,0,0.14)';
          g.fillRect(0, rand(i + 900) * h, w, 1 + rand(i + 902) * 3);
        }
      }, false);
    }
    function strawberryTexture() {
      return canvasTex(256, 256, function (g, w, h) {
        var grd = g.createLinearGradient(0, h, 0, 0);
        grd.addColorStop(0, '#b10f23'); grd.addColorStop(0.75, '#d3172c'); grd.addColorStop(1, '#f06a4a');
        g.fillStyle = grd; g.fillRect(0, 0, w, h);
        for (var r = 0; r < 14; r++) for (var c = 0; c < 18; c++) {
          var x = (c + (r % 2) * 0.5) * (w / 18), y = r * (h / 14) + 6;
          g.fillStyle = 'rgba(120,0,10,0.5)'; g.beginPath(); g.ellipse(x, y, 4, 5, 0, 0, 7); g.fill();
          g.fillStyle = '#f2d36b'; g.beginPath(); g.ellipse(x, y - 1, 1.6, 2.4, 0, 0, 7); g.fill();
        }
      });
    }
    function kiwiTexture() {
      return canvasTex(256, 256, function (g, w, h) {
        var cx = w / 2, cy = h / 2;
        g.fillStyle = '#6b5131'; g.fillRect(0, 0, w, h);
        var grd = g.createRadialGradient(cx, cy, 6, cx, cy, w * 0.49);
        grd.addColorStop(0, '#fbf7d8'); grd.addColorStop(0.2, '#e6efb0'); grd.addColorStop(0.32, '#9ccc3d'); grd.addColorStop(0.92, '#6fb02a'); grd.addColorStop(1, '#4f7d22');
        g.fillStyle = grd; g.beginPath(); g.arc(cx, cy, w * 0.48, 0, 7); g.fill();
        g.fillStyle = '#1b130c';
        for (var s = 0; s < 46; s++) {
          var b = (s / 46) * Math.PI * 2, rr = 36 + rand(s + 50) * 9;
          g.save(); g.translate(cx + Math.cos(b) * rr, cy + Math.sin(b) * rr); g.rotate(b);
          g.beginPath(); g.ellipse(0, 0, 4.2, 2, 0, 0, 7); g.fill(); g.restore();
        }
      });
    }
    function woodTexture() {
      return canvasTex(512, 512, function (g, w, h) {
        g.fillStyle = '#9a6a3d'; g.fillRect(0, 0, w, h);
        for (var i = 0; i < 90; i++) {
          g.strokeStyle = rand(i) > 0.5 ? 'rgba(70,40,15,0.25)' : 'rgba(210,160,100,0.18)';
          g.lineWidth = 1 + rand(i + 1) * 3;
          var y = rand(i + 2) * h;
          g.beginPath(); g.moveTo(0, y);
          for (var x = 0; x <= w; x += 32) g.lineTo(x, y + Math.sin(x / 60 + i) * 6);
          g.stroke();
        }
      });
    }

    // Soft studio reflections for glossy surfaces, built from a few bright panels.
    function makeEnvironment() {
      var pm = new T.PMREMGenerator(renderer);
      var env = new T.Scene();
      env.add(new T.Mesh(new T.BoxGeometry(12, 12, 12), new T.MeshBasicMaterial({ color: 0x24150d, side: T.BackSide })));
      [[5, 3, [5.5, 4.8, 4.0], 3, 4, 4], [3, 4, [1.6, 1.4, 1.2], -5, 1, 2], [4, 2, [3.0, 1.6, 0.7], -2, 2, -5], [6, 6, [0.5, 0.42, 0.34], 0, 5.8, 0]].forEach(function (p) {
        var m = new T.Mesh(new T.PlaneGeometry(p[0], p[1]), new T.MeshBasicMaterial({ color: new T.Color(p[2][0], p[2][1], p[2][2]), side: T.DoubleSide }));
        m.position.set(p[3], p[4], p[5]);
        m.lookAt(0, 0, 0);
        env.add(m);
      });
      var tex = pm.fromScene(env, 0.04).texture;
      pm.dispose();
      return tex;
    }

    function rosetteGeo(radius, height, ridges) {
      var prof = [];
      for (var i = 0; i <= 14; i++) {
        var q = i / 14;
        prof.push(new T.Vector2(Math.max(radius * Math.pow(Math.sin(Math.PI * 0.5 * (1 - q) + 0.05), 0.9) * (1 - 0.15 * q), 0.002), q * height));
      }
      var geo = new T.LatheGeometry(prof, 64), pos = geo.attributes.position;
      for (var j = 0; j < pos.count; j++) {
        var x = pos.getX(j), y = pos.getY(j), z = pos.getZ(j), hk = y / height;
        var f = 1 + 0.16 * Math.cos(ridges * (Math.atan2(z, x) + hk * 2.4)) * (1 - hk * 0.6);
        pos.setXYZ(j, x * f, y, z * f);
      }
      geo.computeVertexNormals();
      return geo;
    }
  }

  /* ---------- Helpers ---------- */

  function roundedRectShape(w, h, r) {
    var T = window.THREE, s = new T.Shape();
    s.moveTo(-w / 2 + r, -h / 2);
    s.lineTo(w / 2 - r, -h / 2); s.quadraticCurveTo(w / 2, -h / 2, w / 2, -h / 2 + r);
    s.lineTo(w / 2, h / 2 - r); s.quadraticCurveTo(w / 2, h / 2, w / 2 - r, h / 2);
    s.lineTo(-w / 2 + r, h / 2); s.quadraticCurveTo(-w / 2, h / 2, -w / 2, h / 2 - r);
    s.lineTo(-w / 2, -h / 2 + r); s.quadraticCurveTo(-w / 2, -h / 2, -w / 2 + r, -h / 2);
    return s;
  }
  function shadowed(m) { m.castShadow = true; m.receiveShadow = true; return m; }
  function rand(n) { var x = Math.sin(n * 127.1 + 311.7) * 43758.5453; return x - Math.floor(x); }
  function ease(p) { return p < 0.5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2; }
  function easeOut(p) { return 1 - Math.pow(1 - p, 3); }
  function easeBack(p) { var c = 1.70158, c3 = c + 1; return 1 + c3 * Math.pow(p - 1, 3) + c * Math.pow(p - 1, 2); }
  function webglAvailable() {
    try {
      var c = document.createElement('canvas');
      return !!(window.WebGLRenderingContext && (c.getContext('webgl') || c.getContext('experimental-webgl')));
    } catch (e) { return false; }
  }
})();
