/**
 * TERMINAL DA ORDEM - Cropper 1:1 v2 (canvas-only)
 *
 * Nova abordagem: TUDO desenhado num único <canvas>. Sem <img>, sem
 * transforms acumulados, sem overflow:hidden — a imagem nunca pode
 * "vazar" da área visível porque ela só existe como pixels desenhados.
 *
 * Como funciona:
 *   1. Container [data-cropper] envolve um <input type="file">.
 *   2. JS adiciona um <canvas 320x320> abaixo do input.
 *   3. Quando o usuário escolhe um arquivo, lemos via FileReader,
 *      criamos uma Image() em memória e desenhamos no canvas.
 *   4. Drag (pointer events) move o offset, redesenha.
 *   5. Slider/wheel altera scale, redesenha.
 *   6. Ao submeter, desenhamos num canvas off-screen 800x800 e
 *      substituímos o conteúdo do <input type="file"> por um Blob.
 *
 * Vantagens:
 *   - Imagem JAMAIS sai do canvas (canvas tem dimensões fixas).
 *   - Performance ótima (drawImage é GPU-acelerado).
 *   - Estado simples: { img, scale, offsetX, offsetY }.
 */

(() => {
    'use strict';

    const PALCO  = 320;     // px na tela
    const FINAL  = 800;     // px no arquivo gerado
    const RATIO  = FINAL / PALCO;

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-cropper]').forEach(initCropper);
    });

    function initCropper(host) {
        const input = host.querySelector('input[type="file"]');
        if (!input) return;

        // ---------- UI ----------
        const palco = document.createElement('div');
        palco.className = 'cropper__palco';

        const canvas = document.createElement('canvas');
        canvas.className = 'cropper__canvas';
        canvas.width  = PALCO;
        canvas.height = PALCO;
        palco.appendChild(canvas);

        const controles = document.createElement('div');
        controles.className = 'cropper__controles';
        controles.innerHTML = `
            <button type="button" class="cropper__btn" data-zoom-out aria-label="Diminuir zoom">−</button>
            <input type="range" class="cropper__slider" min="1" max="4" step="0.02" value="1" data-zoom aria-label="Zoom">
            <button type="button" class="cropper__btn" data-zoom-in aria-label="Aumentar zoom">+</button>
            <button type="button" class="cropper__btn cropper__btn--reset" data-reset aria-label="Centralizar">⟲</button>
        `;

        const dica = document.createElement('p');
        dica.className = 'cropper__dica';
        dica.textContent = 'Arraste para reposicionar. Use o slider ou roda do mouse para zoom.';

        // Inserir UI APÓS o input file
        host.append(palco, controles, dica);

        // Inicialmente palco oculto se não houver foto existente nem nova
        const urlExistente = host.dataset.cropperExisting || '';
        if (!urlExistente) {
            host.classList.add('cropper--vazio');
        }

        // ---------- Estado ----------
        const ctx = canvas.getContext('2d');
        const state = {
            img:        null,
            scaleBase:  1,    // escala mínima para "cobrir" o palco
            scaleFator: 1,    // multiplicador acima do base (slider 1..4)
            offsetX:    0,
            offsetY:    0,
            arrastando: false,
            arrasteX:   0,
            arrasteY:   0,
        };

        const slider = controles.querySelector('[data-zoom]');

        function escala() { return state.scaleBase * state.scaleFator; }

        function clampOffsets() {
            // Restringe o offset para que a imagem sempre cubra o palco
            // (sem deixar bordas pretas aparecerem).
            const w = state.img.naturalWidth  * escala();
            const h = state.img.naturalHeight * escala();
            const limX = Math.max(0, (w - PALCO) / 2);
            const limY = Math.max(0, (h - PALCO) / 2);
            state.offsetX = Math.max(-limX, Math.min(limX, state.offsetX));
            state.offsetY = Math.max(-limY, Math.min(limY, state.offsetY));
        }

        function render() {
            // Fundo preto
            ctx.fillStyle = '#000';
            ctx.fillRect(0, 0, PALCO, PALCO);
            if (!state.img) return;

            const w = state.img.naturalWidth  * escala();
            const h = state.img.naturalHeight * escala();
            const x = (PALCO - w) / 2 + state.offsetX;
            const y = (PALCO - h) / 2 + state.offsetY;
            ctx.drawImage(state.img, x, y, w, h);
        }

        function carregarImagem(src) {
            host.classList.remove('cropper--vazio');
            const img = new Image();
            // Para URLs externas (data: e blob: não precisam, http(s) sim)
            if (src.startsWith('http')) img.crossOrigin = 'anonymous';
            img.onload = () => {
                state.img = img;
                // Escala mínima para cobrir o palco em ambas as dimensões
                state.scaleBase  = Math.max(PALCO / img.naturalWidth,
                                            PALCO / img.naturalHeight);
                state.scaleFator = 1;
                state.offsetX    = 0;
                state.offsetY    = 0;
                slider.value     = 1;
                render();
            };
            img.onerror = () => {
                console.warn('Cropper: falha ao carregar', src);
            };
            img.src = src;
        }

        // ---------- Eventos ----------
        input.addEventListener('change', (e) => {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => carregarImagem(ev.target.result);
            reader.readAsDataURL(file);
        });

        if (urlExistente) {
            carregarImagem(urlExistente);
        }

        // Drag (pointer events cobrem mouse + touch)
        canvas.addEventListener('pointerdown', (e) => {
            if (!state.img) return;
            state.arrastando = true;
            state.arrasteX = e.clientX - state.offsetX;
            state.arrasteY = e.clientY - state.offsetY;
            canvas.setPointerCapture(e.pointerId);
        });
        canvas.addEventListener('pointermove', (e) => {
            if (!state.arrastando) return;
            state.offsetX = e.clientX - state.arrasteX;
            state.offsetY = e.clientY - state.arrasteY;
            clampOffsets();
            render();
        });
        const finalizarDrag = (e) => {
            if (!state.arrastando) return;
            state.arrastando = false;
            try { canvas.releasePointerCapture(e.pointerId); } catch (_) {}
        };
        canvas.addEventListener('pointerup',     finalizarDrag);
        canvas.addEventListener('pointercancel', finalizarDrag);

        // Zoom via slider
        slider.addEventListener('input', () => {
            state.scaleFator = parseFloat(slider.value);
            clampOffsets();
            render();
        });

        controles.querySelector('[data-zoom-in]').addEventListener('click', () => {
            state.scaleFator = Math.min(4, state.scaleFator + 0.1);
            slider.value = state.scaleFator;
            clampOffsets();
            render();
        });
        controles.querySelector('[data-zoom-out]').addEventListener('click', () => {
            state.scaleFator = Math.max(1, state.scaleFator - 0.1);
            slider.value = state.scaleFator;
            clampOffsets();
            render();
        });
        controles.querySelector('[data-reset]').addEventListener('click', () => {
            state.scaleFator = 1;
            state.offsetX = 0;
            state.offsetY = 0;
            slider.value = 1;
            render();
        });

        // Wheel para zoom (apenas quando o cursor está sobre o canvas)
        canvas.addEventListener('wheel', (e) => {
            if (!state.img) return;
            e.preventDefault();
            const delta = e.deltaY < 0 ? 0.05 : -0.05;
            state.scaleFator = Math.max(1, Math.min(4, state.scaleFator + delta));
            slider.value = state.scaleFator;
            clampOffsets();
            render();
        }, { passive: false });

        // ---------- Submit do form: substituir o file pelo blob recortado ----------
        const form = host.closest('form');
        if (form) {
            form.addEventListener('submit', interceptarSubmit);
        }

        async function interceptarSubmit(e) {
            const arquivoSelecionado = input.files && input.files[0];
            if (!arquivoSelecionado || !state.img) {
                // sem arquivo novo escolhido — submete normal (preserva foto existente)
                return;
            }
            e.preventDefault();
            try {
                const blob = await renderizarSaida();
                const cortado = new File([blob], 'crop.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(cortado);
                input.files = dt.files;
            } catch (err) {
                console.warn('Cropper: falha ao recortar — submetendo arquivo original.', err);
            }
            form.submit();
        }

        function renderizarSaida() {
            return new Promise((resolve, reject) => {
                const out = document.createElement('canvas');
                out.width = FINAL;
                out.height = FINAL;
                const octx = out.getContext('2d');
                // Mesma composição do preview, mas escalada para FINAL.
                const w = state.img.naturalWidth  * escala() * RATIO;
                const h = state.img.naturalHeight * escala() * RATIO;
                const x = (FINAL - w) / 2 + state.offsetX * RATIO;
                const y = (FINAL - h) / 2 + state.offsetY * RATIO;
                octx.drawImage(state.img, x, y, w, h);
                out.toBlob((blob) => {
                    if (blob) resolve(blob);
                    else reject(new Error('toBlob retornou null'));
                }, 'image/jpeg', 0.9);
            });
        }
    }
})();
