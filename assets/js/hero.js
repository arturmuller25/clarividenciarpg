/**
 * TERMINAL DA ORDEM — Hero D20 cinematografica.
 *
 * Animacao 3D dinamica do icosaedro com paragem matematicamente exata
 * na face 20 (RESTING * integer turns) + 2 fases de audio.
 *
 * SEQUENCIA (timeline ms):
 *   0     : await audioQueda.play() — sincroniza visual + som no mesmo tick
 *   0     - 1400  : queda do topo + scale-in (easeIn) + offset X esquerdo
 *   1400  - 1700  : bounce/squash no impacto
 *   0     - 3200  : tumble decelerante (rollEase) com integer turns por
 *                   eixo (4*X / 5*Y / 3*Z) => em p=1, R_tumble = identity
 *   1760  - 3200  : late wobble com decay quadratico => 0 em p=1
 *   3100+         : burst de particulas (28) + sustain interval 220ms
 *   3200          : RESTING (face 20 exata) + audio queda termina natural
 *   3700          : titulo entra (CSS) + iniciarLoop() (audio loop play
 *                   + fade-in 1s; volume 0 -> 0.5)
 *   4800          : subtitulo entra (CSS)
 *   5000          : botao "// ROMPER O VEU" aparece (CSS keyframe 0.8s fade-in)
 *   click         : fadeOutLoopEFechar — fade do loop (800ms) + .is-saindo
 *                   no panel (sem fadeout automatico, espera o click)
 *
 * SINCRONIA FRAME-A-FRAME do audio de queda:
 *   Apos audio.load() no DOMContentLoaded (pre-cache), o async dispararAnimacao
 *   faz `await audioQueda.play()` antes do primeiro RAF. play() resolve
 *   quando o audio realmente comecou. RAF dispara ~16ms depois (1 frame).
 *   Desync residual <= 16ms (limite hard do refresh rate). Imperceptivel.
 *
 * AUTOPLAY POLICY:
 *   - Audio queda: try play(); se falhar (Promise rejected), animacao roda
 *     silenciosa. Sem botao fallback (removido nesta sessao).
 *   - Audio loop: try play() em 3.7s; se falhar, instala click listener
 *     global que toca no PRIMEIRO click do usuario em qualquer lugar.
 *
 * DECISAO 019: gate first-visit/F5 preservado.
 *
 * REDUCED MOTION: short-circuit para pose final estatica (RESTING) sem
 * loop 3D, sem particulas. Audio + botao Continuar funcionam normal.
 */
(() => {
    'use strict';

    /* =========================================================
     * GATE — Hero so roda na primeira visita ou em F5 (Decisao 019)
     * ========================================================= */
    function deveRodarHero() {
        const navType = detectarNavType();
        const jaVisto = sessionStorage.getItem('terminalHeroVisto') === '1';
        return !jaVisto || navType === 'reload';
    }
    function detectarNavType() {
        try {
            const entries = performance.getEntriesByType('navigation');
            if (entries && entries.length > 0 && entries[0].type) return entries[0].type;
        } catch (_) { /* ignora */ }
        if (performance.navigation) {
            const t = performance.navigation.type;
            if (t === 1) return 'reload';
            if (t === 2) return 'back_forward';
        }
        return 'navigate';
    }

    /* =========================================================
     * GEOMETRIA DO ICOSAEDRO REGULAR
     * 12 vertices via phi (razao aurea). 20 faces triangulares.
     * FACE_NUMBERS segue d20 padrao (faces opostas somam 21).
     * face[0] = numero 20 -> alvo do RESTING.
     * ========================================================= */
    const PHI    = (1 + Math.sqrt(5)) / 2;
    const NORMV  = Math.sqrt(1 + PHI * PHI);
    const VERTS_RAW = [
        [-1,  PHI,  0], [ 1,  PHI,  0], [-1, -PHI,  0], [ 1, -PHI,  0],
        [ 0, -1,  PHI], [ 0,  1,  PHI], [ 0, -1, -PHI], [ 0,  1, -PHI],
        [ PHI,  0, -1], [ PHI,  0,  1], [-PHI,  0, -1], [-PHI,  0,  1]
    ];
    const V = VERTS_RAW.map(v => v.map(c => c / NORMV));
    const FACES = [
        [0,11, 5], [0, 5, 1], [0, 1, 7], [0, 7,10], [0,10,11],
        [1, 5, 9], [5,11, 4], [11,10,2], [10,7, 6], [7, 1, 8],
        [3, 9, 4], [3, 4, 2], [3, 2, 6], [3, 6, 8], [3, 8, 9],
        [4, 9, 5], [2, 4,11], [6, 2,10], [8, 6, 7], [9, 8, 1]
    ];
    const FACE_NUMBERS = [20, 8,14,12, 4, 17, 2,11, 9,16, 7,15, 3,19,13, 6,10, 5, 1,18];

    /* Centro de cada face (media aritmetica dos 3 vertices) */
    const FACE_CENTER = FACES.map(f => {
        const c = [0, 0, 0];
        for (const i of f) for (let k = 0; k < 3; k++) c[k] += V[i][k] / 3;
        return c;
    });

    /* =========================================================
     * ALGEBRA DE MATRIZES 3x3
     * ========================================================= */
    const mul = (M, v) => [
        M[0][0]*v[0] + M[0][1]*v[1] + M[0][2]*v[2],
        M[1][0]*v[0] + M[1][1]*v[1] + M[1][2]*v[2],
        M[2][0]*v[0] + M[2][1]*v[1] + M[2][2]*v[2]
    ];
    const rotX = a => { const c = Math.cos(a), s = Math.sin(a); return [[1,0,0],[0,c,-s],[0,s,c]]; };
    const rotY = a => { const c = Math.cos(a), s = Math.sin(a); return [[c,0,s],[0,1,0],[-s,0,c]]; };
    const rotZ = a => { const c = Math.cos(a), s = Math.sin(a); return [[c,-s,0],[s,c,0],[0,0,1]]; };
    const mm = (A, B) => {
        const R = [[0,0,0],[0,0,0],[0,0,0]];
        for (let i = 0; i < 3; i++)
            for (let j = 0; j < 3; j++)
                R[i][j] = A[i][0]*B[0][j] + A[i][1]*B[1][j] + A[i][2]*B[2][j];
        return R;
    };
    const lerp     = (a, b, t) => a + (b - a) * t;
    const easeOut  = t => 1 - Math.pow(1 - t, 3);
    const easeIn   = t => t * t * t;
    const rollEase = p => 1 - Math.pow(1 - p, 4);

    /* alignMat: rotacao que leva o vetor `from` ao vetor `to` (Rodrigues) */
    function alignMat(from, to) {
        const dot = from[0]*to[0] + from[1]*to[1] + from[2]*to[2];
        if (dot >  0.9999) return [[1,0,0],[0,1,0],[0,0,1]];
        if (dot < -0.9999) return [[1,0,0],[0,-1,0],[0,0,-1]];
        const ax = [
            from[1]*to[2] - from[2]*to[1],
            from[2]*to[0] - from[0]*to[2],
            from[0]*to[1] - from[1]*to[0]
        ];
        const al = Math.hypot(ax[0], ax[1], ax[2]);
        const k = [ax[0]/al, ax[1]/al, ax[2]/al];
        const c = dot, s = al, t = 1 - c;
        return [
            [t*k[0]*k[0]+c,        t*k[0]*k[1]-s*k[2],   t*k[0]*k[2]+s*k[1]],
            [t*k[0]*k[1]+s*k[2],   t*k[1]*k[1]+c,        t*k[1]*k[2]-s*k[0]],
            [t*k[0]*k[2]-s*k[1],   t*k[1]*k[2]+s*k[0],   t*k[2]*k[2]+c]
        ];
    }

    /* RESTING: pose final desejada — face 20 voltada para a camera (+Z). */
    const _f0    = FACE_CENTER[0];
    const _n0Len = Math.hypot(_f0[0], _f0[1], _f0[2]);
    const _n0    = [_f0[0]/_n0Len, _f0[1]/_n0Len, _f0[2]/_n0Len];
    const RESTING = alignMat(_n0, [0, 0, 1]);

    /* Timeline — integer turns por eixo => em p=1, R_tumble = I.
     * T_SETTLE_END calibrado para CASAR com a duracao do som
     * `som_para_a_hero.mp3` (~3.19s @ 320 kbps): som termina natural
     * exatamente quando o dado para (RESTING). Tumble mais frenetico
     * (mesma quantidade de turns em menos tempo) que a versao 4.5s. */
    const T_SETTLE_END = 3200;
    const TURNS_X = 4, TURNS_Y = 5, TURNS_Z = 3;
    const TOTAL_X = TURNS_X * 2 * Math.PI;
    const TOTAL_Y = TURNS_Y * 2 * Math.PI;
    const TOTAL_Z = TURNS_Z * 2 * Math.PI;

    /* Constantes de render */
    const NS    = 'http://www.w3.org/2000/svg';
    const VB    = 400;
    const SCALE = 145;

    /* Cores envenenadas (Decisao D4) */
    const COR_GOLD       = '#e0b53d';
    const COR_GOLD_DEEP  = 'rgba(170, 120, 20, 0.65)';
    const COR_GOLD_GLOW  = 'rgba(170, 120, 20, 0.7)';
    const COR_TEXT_DARK  = '#0a0418';

    /* =========================================================
     * BOOTSTRAP
     * ========================================================= */
    document.addEventListener('DOMContentLoaded', () => {
        const hero = document.querySelector('.hero');
        if (!hero) return;

        if (!deveRodarHero()) {
            hero.style.display = 'none';
            hero.setAttribute('aria-hidden', 'true');
            return;
        }
        try { sessionStorage.setItem('terminalHeroVisto', '1'); } catch (_) {}

        const dice           = hero.querySelector('#hero-dice');
        const particles      = hero.querySelector('#hero-particles');
        const botaoContinuar = hero.querySelector('.hero__continuar');
        const audioQuedaSrc  = hero.dataset.audio || '';
        const audioLoopSrc   = hero.dataset.audioLoop || '';

        /* Audio queda (sound do impacto/queda do dado, ~3.19s) */
        let audioQueda = null;
        if (audioQuedaSrc) {
            audioQueda = new Audio(audioQuedaSrc);
            audioQueda.preload = 'auto';
            audioQueda.volume  = 0.7;
            audioQueda.load();   /* pre-cache para minimizar latencia do play() */
        }

        /* Audio loop ambiente (toca apos a queda, fade-in suave) */
        let audioLoop = null;
        if (audioLoopSrc) {
            audioLoop = new Audio(audioLoopSrc);
            audioLoop.preload = 'auto';
            audioLoop.loop    = true;
            audioLoop.volume  = 0;
            audioLoop.load();    /* pre-cache para o loop nao demorar a iniciar em 3.7s */
        }

        /* Flags de orquestracao */
        let _heroFinalizado = false;   /* true apos click em "Continuar" */
        let _loopFading     = false;   /* lock para evitar fadeOuts concorrentes */

        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const T_LOOP_START = 3700;     /* ms — coincide com title reveal CSS */

        /* Constroi SVG dinamico (20 polygons + 20 texts) */
        const svg = document.createElementNS(NS, 'svg');
        svg.setAttribute('viewBox', `${-VB/2} ${-VB/2} ${VB} ${VB}`);
        svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        svg.setAttribute('aria-hidden', 'true');
        svg.style.cssText = 'width:100%;height:100%;display:block;overflow:visible;';

        const defs = document.createElementNS(NS, 'defs');
        defs.innerHTML =
            '<radialGradient id="hd20-dim" cx="50%" cy="38%" r="75%">' +
                '<stop offset="0%" stop-color="#3a1660" stop-opacity="0.92"/>' +
                '<stop offset="55%" stop-color="#1a0a2e" stop-opacity="0.95"/>' +
                '<stop offset="100%" stop-color="#070314" stop-opacity="0.98"/>' +
            '</radialGradient>' +
            '<radialGradient id="hd20-mid" cx="50%" cy="38%" r="75%">' +
                '<stop offset="0%" stop-color="#54208a" stop-opacity="0.96"/>' +
                '<stop offset="55%" stop-color="#240e44" stop-opacity="0.96"/>' +
                '<stop offset="100%" stop-color="#0d061e" stop-opacity="0.97"/>' +
            '</radialGradient>' +
            '<radialGradient id="hd20-lit" cx="50%" cy="38%" r="75%">' +
                '<stop offset="0%" stop-color="#7430b8" stop-opacity="1"/>' +
                '<stop offset="55%" stop-color="#341464" stop-opacity="0.97"/>' +
                '<stop offset="100%" stop-color="#10081e" stop-opacity="0.97"/>' +
            '</radialGradient>';
        svg.appendChild(defs);

        const polyLayer = document.createElementNS(NS, 'g');
        const textLayer = document.createElementNS(NS, 'g');
        svg.appendChild(polyLayer);
        svg.appendChild(textLayer);

        const polys = [], texts = [];
        for (let i = 0; i < 20; i++) {
            const p = document.createElementNS(NS, 'polygon');
            p.setAttribute('stroke-linejoin', 'round');
            p.setAttribute('stroke-linecap',  'round');
            polyLayer.appendChild(p);
            polys.push(p);

            const t = document.createElementNS(NS, 'text');
            t.setAttribute('font-family',       'JetBrains Mono, ui-monospace, monospace');
            t.setAttribute('font-weight',       '700');
            t.setAttribute('text-anchor',       'middle');
            t.setAttribute('dominant-baseline', 'central');
            t.setAttribute('paint-order',       'stroke');
            t.setAttribute('stroke',            COR_TEXT_DARK);
            t.setAttribute('stroke-width',      '1.2');
            t.textContent = FACE_NUMBERS[i];
            textLayer.appendChild(t);
            texts.push(t);
        }
        if (dice) dice.appendChild(svg);

        /* =========================================================
         * COMPUTE POSE — retorna {rot, tx, ty, scale} para o tempo t
         * ========================================================= */
        function computePose(t) {
            let rot, ty = 0, tx = 0, scale = 1;

            if (t < T_SETTLE_END) {
                const p = t / T_SETTLE_END;
                const e = rollEase(p);
                let ax = TOTAL_X * e;
                let ay = TOTAL_Y * e;
                let az = TOTAL_Z * e;
                /* Late wobble: decai a 0 em p=1 (preserva RESTING exato) */
                if (p > 0.55) {
                    const wp  = (p - 0.55) / 0.45;
                    const wob = Math.sin(wp * Math.PI * 3.0) * Math.pow(1 - wp, 2) * 0.16;
                    ax += wob;
                    az += wob * 0.55;
                }
                /* CRITICAL: tumble × RESTING. Em p=1, tumble = I. */
                rot = mm(rotX(ax), mm(rotY(ay), mm(rotZ(az), RESTING)));

                if (t < 1400) {
                    /* Drop-in */
                    const dp = t / 1400;
                    ty    = lerp(-window.innerHeight * 0.45, 0, easeIn(dp));
                    tx    = lerp(-window.innerWidth  * 0.04, 0, easeOut(dp));
                    scale = lerp(0.7, 1.0, easeIn(dp));
                } else if (t < 1700) {
                    /* Bounce + squash */
                    const bp = (t - 1400) / 300;
                    ty    = -Math.sin(bp * Math.PI) * 32;
                    scale = bp < 0.3 ? 1 + (0.3 - bp) / 0.3 * 0.15 : 1;
                }
            } else {
                /* Hover infinito apos assentamento */
                const tt = (t - T_SETTLE_END) / 1000;
                ty    = Math.sin(tt * 1.0) * 9;
                tx    = Math.cos(tt * 0.7) * 5;
                const wx = Math.cos(tt * 0.42) - 1;
                const wy = Math.sin(tt * 0.55);
                rot   = mm(rotY(wy * 0.18), mm(rotX(wx * 0.13), RESTING));
                scale = 1.0;
            }
            return { rot, tx, ty, scale };
        }

        /* =========================================================
         * RENDER POSE — aplica transform + redesenha 20 faces
         * ========================================================= */
        function renderPose(pose, t) {
            const { rot, tx, ty, scale } = pose;
            if (dice) dice.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;

            /* Project 12 vertices */
            const P = V.map(v => mul(rot, v));

            /* Coleta dados de cada face */
            const items = [];
            for (let i = 0; i < FACES.length; i++) {
                const f = FACES[i];
                const a = P[f[0]], b = P[f[1]], c = P[f[2]];
                /* Screen normal (z) para back-face culling */
                const ux = b[0]-a[0], uy = b[1]-a[1];
                const vx = c[0]-a[0], vy = c[1]-a[1];
                const front = (ux*vy - uy*vx) > 0;
                /* 3D normal para shading */
                const aax = b[0]-a[0], aay = b[1]-a[1], aaz = b[2]-a[2];
                const bbx = c[0]-a[0], bby = c[1]-a[1], bbz = c[2]-a[2];
                const nx  = aay*bbz - aaz*bby;
                const ny  = aaz*bbx - aax*bbz;
                const nz  = aax*bby - aay*bbx;
                const nl  = Math.hypot(nx, ny, nz) || 1;
                const nzN = nz / nl;
                const avgZ = (a[2]+b[2]+c[2]) / 3;
                const cx = ((a[0]+b[0]+c[0]) / 3) * SCALE;
                const cy = -((a[1]+b[1]+c[1]) / 3) * SCALE;
                items.push({ i, a, b, c, avgZ, cx, cy, front, nzN });
            }
            /* Painter's algorithm: back to front */
            items.sort((x, y) => x.avgZ - y.avgZ);

            for (const it of items) {
                const { i, a, b, c, cx, cy, front, nzN } = it;
                const poly = polys[i];
                const text = texts[i];
                const num  = FACE_NUMBERS[i];

                if (!front) {
                    poly.style.display = 'none';
                    text.style.display = 'none';
                } else {
                    poly.style.display = '';
                    text.style.display = '';
                    poly.setAttribute('points',
                        `${(a[0]*SCALE).toFixed(2)},${(-a[1]*SCALE).toFixed(2)} ` +
                        `${(b[0]*SCALE).toFixed(2)},${(-b[1]*SCALE).toFixed(2)} ` +
                        `${(c[0]*SCALE).toFixed(2)},${(-c[1]*SCALE).toFixed(2)}`
                    );
                    const lit = Math.max(0, nzN);
                    let grad = 'hd20-dim';
                    if      (lit > 0.78) grad = 'hd20-lit';
                    else if (lit > 0.45) grad = 'hd20-mid';
                    poly.setAttribute('fill', `url(#${grad})`);
                    poly.setAttribute('stroke', `rgba(224,181,61,${(0.45 + lit * 0.55).toFixed(2)})`);
                    poly.setAttribute('stroke-width', (0.9 + lit * 1.7).toFixed(2));
                    poly.style.filter = lit > 0.5 ? `drop-shadow(0 0 3px ${COR_GOLD_GLOW})` : '';

                    text.setAttribute('x', cx.toFixed(2));
                    text.setAttribute('y', cy.toFixed(2));
                    if (lit < 0.18) {
                        text.style.display = 'none';
                    } else {
                        text.setAttribute('font-size', (12 + lit * 8).toFixed(1));
                        if (num === 20 && t > T_SETTLE_END - 700) {
                            /* Number 20 fades in com glow dourado durante o assentamento */
                            const fadeIn = Math.min(1, (t - (T_SETTLE_END - 700)) / 600);
                            text.setAttribute('fill',         COR_GOLD);
                            text.setAttribute('font-size',    '26');
                            text.setAttribute('stroke-width', '0.6');
                            text.setAttribute('opacity',      fadeIn.toFixed(2));
                            text.style.filter =
                                `drop-shadow(0 0 10px ${COR_GOLD}) drop-shadow(0 0 18px ${COR_GOLD_DEEP})`;
                        } else {
                            text.setAttribute('fill', `rgba(224,181,61,${(0.4 + lit * 0.5).toFixed(2)})`);
                            text.removeAttribute('opacity');
                            text.style.filter = '';
                            text.setAttribute('stroke-width', '1.2');
                        }
                    }
                }
                /* Reordena DOM por z (painter's) */
                polyLayer.appendChild(poly);
                textLayer.appendChild(text);
            }
        }

        /* =========================================================
         * PARTICULAS — 4 elementos coloridos flutuando
         * ========================================================= */
        const COLORES = [
            ['#a53846', 'rgba(165, 56, 70, 0.85)'],     /* sangue antigo  */
            ['#7b4d9e', 'rgba(123, 77,158, 0.85)'],     /* energia        */
            ['#e0b53d', 'rgba(224,181, 61, 0.85)'],     /* conhecimento   */
            ['#bdc3c7', 'rgba(189,195,199, 0.65)']      /* morte          */
        ];
        function spawnParticula() {
            if (!particles) return;
            const p   = document.createElement('div');
            p.className = 'particle';
            const col = COLORES[Math.floor(Math.random() * COLORES.length)];
            p.style.background = col[0];
            p.style.boxShadow  = `0 0 8px ${col[1]}, 0 0 16px ${col[1]}`;
            const sz = 2 + Math.random() * 3.5;
            p.style.width  = sz + 'px';
            p.style.height = sz + 'px';
            p.style.left   = (Math.random() * 100) + 'vw';
            p.style.bottom = (-5 - Math.random() * 10) + 'px';
            const dur = 7 + Math.random() * 9;
            p.style.animationDuration = dur + 's';
            p.style.setProperty('--drift', ((Math.random() * 60) - 30) + 'vw');
            particles.appendChild(p);
            window.setTimeout(() => p.remove(), dur * 1000 + 200);
        }
        let _intervalParticulas = null;
        function iniciarParticulas() {
            for (let i = 0; i < 28; i++) window.setTimeout(spawnParticula, i * 70);
            _intervalParticulas = window.setInterval(spawnParticula, 220);
        }

        /* =========================================================
         * ORQUESTRACAO
         * ========================================================= */
        let t0 = 0;
        function stepLoop(now) {
            const t = now - t0;
            renderPose(computePose(t), t);
            if (!hero.classList.contains('is-saindo')) {
                requestAnimationFrame(stepLoop);
            }
        }

        /* dispararAnimacao — orquestra audio queda + animacao 3D + agendamento
         * do loop ambiente. Sem fadeout automatico do panel (espera click). */
        async function dispararAnimacao() {
            hero.classList.add('is-rodando');

            /* SINCRONIA FRAME-A-FRAME: aguarda audio.play() resolver (audio
             * realmente comecou) ANTES do primeiro RAF da visual. play() returns
             * Promise — quando ela resolve, o som comecou. RAF dispara ~16ms
             * depois (1 frame). Desync residual <= 16ms (limite do refresh rate
             * do browser). Imperceptivel. */
            if (audioQueda) {
                try { await audioQueda.play(); } catch (_) { /* sem som — segue silenciosa */ }
            }

            if (reduced) {
                /* Pose RESTING estatica, sem RAF loop, sem particulas */
                renderPose({ rot: RESTING, tx: 0, ty: 0, scale: 1 }, T_SETTLE_END + 1000);
            } else {
                /* Primeiro frame iniciado no proprio RAF (sem 1-frame extra de lag).
                 * stepLoop(ts) renderiza imediatamente com t=0, depois pede o
                 * proximo RAF dentro de stepLoop. */
                requestAnimationFrame((ts) => {
                    t0 = ts;
                    stepLoop(ts);
                });
                window.setTimeout(iniciarParticulas, T_SETTLE_END - 100);
            }

            /* Loop ambiente comeca quando o titulo aparece (3.7s a partir DESTE
             * ponto — apos o await play(), entao relativo ao inicio real da visual). */
            window.setTimeout(iniciarLoop, T_LOOP_START);
        }

        /* iniciarLoop — try play; se autoplay bloqueado, instala click listener
         * global que libera o loop no PRIMEIRO click do usuario em qualquer lugar. */
        async function iniciarLoop() {
            if (_heroFinalizado || !audioLoop) return;
            audioLoop.volume = 0;
            try {
                await audioLoop.play();
                fadeInLoop();
            } catch (_) {
                /* Autoplay do loop bloqueado — primeiro click libera */
                const ativarApos = () => {
                    if (_heroFinalizado || !audioLoop) return;
                    audioLoop.play().then(fadeInLoop).catch(() => { /* ignore */ });
                };
                document.addEventListener('click', ativarApos, { once: true });
            }
        }

        /* fadeInLoop — volume 0 -> 0.5 em 1s (suave apos silencio do assentamento) */
        function fadeInLoop() {
            if (_heroFinalizado || !audioLoop) return;
            const VOL_TARGET = 0.5;
            const DUR        = 1000;
            const start      = performance.now();
            function passo(now) {
                if (_heroFinalizado || !audioLoop) return;
                const t = Math.min(1, (now - start) / DUR);
                audioLoop.volume = VOL_TARGET * t;
                if (t < 1) requestAnimationFrame(passo);
            }
            requestAnimationFrame(passo);
        }

        /* fadeOutLoopEFechar — disparado pelo click no botao "// ROMPER O VEU".
         * Fade do volume atual -> 0 (800ms), pause, depois .is-saindo no panel.
         * Lock _heroFinalizado bloqueia callbacks pendentes (fadeInLoop, click
         * listener global do iniciarLoop, etc). */
        function fadeOutLoopEFechar() {
            if (_loopFading || _heroFinalizado) return;
            _heroFinalizado = true;
            _loopFading     = true;

            const DUR   = 800;
            const v0    = audioLoop ? audioLoop.volume : 0;
            const start = performance.now();
            function passo(now) {
                const t = Math.min(1, (now - start) / DUR);
                if (audioLoop) audioLoop.volume = v0 * (1 - t);
                if (t < 1) {
                    requestAnimationFrame(passo);
                } else {
                    if (audioLoop) {
                        audioLoop.pause();
                        audioLoop.volume = v0;
                    }
                    hero.classList.add('is-saindo');
                    if (_intervalParticulas) clearInterval(_intervalParticulas);
                    window.setTimeout(() => {
                        hero.style.display = 'none';
                        hero.setAttribute('aria-hidden', 'true');
                    }, 950);
                }
            }
            requestAnimationFrame(passo);
        }

        if (botaoContinuar) {
            botaoContinuar.addEventListener('click', fadeOutLoopEFechar);
        }

        dispararAnimacao();
    });
})();
