/**
 * TERMINAL DA ORDEM - Cropper vanilla 1:1
 *
 * Componente reutilizável para upload de imagens com:
 *   - Preview em quadro 1:1 (square mask sobre o palco)
 *   - Drag para reposicionar a imagem dentro do quadro
 *   - Slider de zoom (também aceita scroll wheel)
 *   - Substituição transparente do <input type="file"> no submit:
 *     o cropper desenha a região 1:1 selecionada num <canvas> e
 *     entrega o resultado como Blob/JPEG no FormData.
 *
 * Como usar (HTML):
 *   <div data-cropper data-cropper-input="foto">
 *       <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
 *       (o resto do markup do cropper é gerado em runtime)
 *   </div>
 *
 * O atributo data-cropper-input casa com o name do <input file>; ao submeter
 * o form, o JS substitui o conteúdo desse input por um File cortado a 1:1.
 *
 * Tamanho final: 800x800 JPEG, qualidade 0.9. Adequado para avatares/capas.
 */

(() => {
    'use strict';

    const TAMANHO_FINAL = 800;     // pixels (lado do quadrado de saída)
    const TAMANHO_PALCO = 320;     // pixels (lado visível do cropper na UI)

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-cropper]').forEach(initCropper);
    });

    function initCropper(host) {
        const input = host.querySelector('input[type="file"]');
        if (!input) return;

        // Estado interno do cropper
        const estado = {
            img:     null,   // HTMLImageElement carregada
            scale:   1,      // multiplicador
            offsetX: 0,      // translação x da imagem (pixels)
            offsetY: 0,      // translação y da imagem (pixels)
            arrastando: false,
            arrasteX: 0,
            arrasteY: 0,
        };

        // ----- Construir markup ao redor do input -----
        const palco   = document.createElement('div');
        palco.className = 'cropper__palco';
        const imgEl  = document.createElement('img');
        imgEl.className = 'cropper__img';
        imgEl.alt = '';
        imgEl.draggable = false;
        const mascara = document.createElement('div');
        mascara.className = 'cropper__mascara';
        palco.append(imgEl, mascara);

        const controles = document.createElement('div');
        controles.className = 'cropper__controles';
        controles.innerHTML = `
            <button type="button" class="cropper__btn" data-zoom-out aria-label="Diminuir zoom">−</button>
            <input type="range" class="cropper__slider" min="0.5" max="3" step="0.02" value="1" aria-label="Zoom">
            <button type="button" class="cropper__btn" data-zoom-in aria-label="Aumentar zoom">+</button>
            <button type="button" class="cropper__btn cropper__btn--reset" data-reset aria-label="Centralizar">⟲</button>
        `;
        const dica = document.createElement('p');
        dica.className = 'cropper__dica';
        dica.textContent = 'Arraste a imagem para reposicionar. Use a roda do mouse ou o slider para zoom.';

        host.append(palco, controles, dica);

        // Estado inicial: mostrar palco apenas se já houver imagem prévia (data-cropper-existing)
        const urlExistente = host.dataset.cropperExisting || '';
        if (urlExistente) {
            carregarImagem(urlExistente, false);
        } else {
            host.classList.add('cropper--vazio');
        }

        // ===== Event listeners =====
        input.addEventListener('change', (e) => {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => carregarImagem(ev.target.result, true);
            reader.readAsDataURL(file);
        });

        const slider = controles.querySelector('.cropper__slider');
        slider.addEventListener('input', () => {
            estado.scale = parseFloat(slider.value);
            atualizarTransform();
        });

        controles.querySelector('[data-zoom-in]').addEventListener('click', () => {
            estado.scale = Math.min(3, estado.scale + 0.1);
            slider.value = estado.scale;
            atualizarTransform();
        });
        controles.querySelector('[data-zoom-out]').addEventListener('click', () => {
            estado.scale = Math.max(0.5, estado.scale - 0.1);
            slider.value = estado.scale;
            atualizarTransform();
        });
        controles.querySelector('[data-reset]').addEventListener('click', () => {
            estado.scale = 1;
            estado.offsetX = 0;
            estado.offsetY = 0;
            slider.value = 1;
            atualizarTransform();
        });

        // Drag para reposicionar
        palco.addEventListener('pointerdown', (e) => {
            if (!estado.img) return;
            estado.arrastando = true;
            estado.arrasteX = e.clientX - estado.offsetX;
            estado.arrasteY = e.clientY - estado.offsetY;
            palco.setPointerCapture(e.pointerId);
        });
        palco.addEventListener('pointermove', (e) => {
            if (!estado.arrastando) return;
            estado.offsetX = e.clientX - estado.arrasteX;
            estado.offsetY = e.clientY - estado.arrasteY;
            atualizarTransform();
        });
        palco.addEventListener('pointerup',     (e) => { estado.arrastando = false; palco.releasePointerCapture?.(e.pointerId); });
        palco.addEventListener('pointercancel', ()  => { estado.arrastando = false; });

        // Wheel para zoom
        palco.addEventListener('wheel', (e) => {
            if (!estado.img) return;
            e.preventDefault();
            const delta = e.deltaY < 0 ? 0.05 : -0.05;
            estado.scale = Math.max(0.5, Math.min(3, estado.scale + delta));
            slider.value = estado.scale;
            atualizarTransform();
        }, { passive: false });

        // Submit do form ao redor: cortar a 1:1 e substituir o file input
        const form = host.closest('form');
        if (form) {
            form.addEventListener('submit', interceptarSubmit);
        }

        // ===== Funções internas =====
        function carregarImagem(src, eh_arquivo_novo) {
            host.classList.remove('cropper--vazio');
            const img = new Image();
            // crossOrigin somente para URLs externas — para data: e blob: não precisa
            if (src.startsWith('http')) img.crossOrigin = 'anonymous';
            img.onload = () => {
                estado.img = img;
                imgEl.src = src;
                // Centralizar e ajustar escala mínima (cobrir o quadrado do palco)
                const escalaParaCobrir = Math.max(TAMANHO_PALCO / img.naturalWidth,
                                                  TAMANHO_PALCO / img.naturalHeight);
                estado.scale = Math.max(1, escalaParaCobrir);
                slider.min = escalaParaCobrir.toFixed(2);
                slider.value = estado.scale;
                estado.offsetX = 0;
                estado.offsetY = 0;
                atualizarTransform();
            };
            img.src = src;
        }

        function atualizarTransform() {
            if (!estado.img) return;
            const { scale, offsetX, offsetY } = estado;
            const w = estado.img.naturalWidth  * scale;
            const h = estado.img.naturalHeight * scale;
            // Centraliza pelo translate baseline + offset do drag
            imgEl.style.width  = w + 'px';
            imgEl.style.height = h + 'px';
            imgEl.style.transform =
                `translate(${offsetX - (w - TAMANHO_PALCO) / 2}px, ${offsetY - (h - TAMANHO_PALCO) / 2}px)`;
        }

        async function interceptarSubmit(e) {
            // Se nao ha imagem nova carregada (apenas existente sem trocar), sai
            const arquivoSelecionado = input.files && input.files[0];
            if (!arquivoSelecionado || !estado.img) {
                return; // deixa o form submeter normalmente sem foto nova
            }

            e.preventDefault();
            try {
                const blob = await renderizarCorte();
                const ext = arquivoSelecionado.type === 'image/png' ? 'png' :
                            arquivoSelecionado.type === 'image/webp' ? 'webp' : 'jpg';
                const cortado = new File([blob], `crop.${ext}`, { type: blob.type });
                // Substitui o conteudo do <input type="file"> pelo blob cortado
                const dt = new DataTransfer();
                dt.items.add(cortado);
                input.files = dt.files;
            } catch (err) {
                console.warn('Falha ao recortar — submetendo arquivo original:', err);
            } finally {
                form.submit();
            }
        }

        function renderizarCorte() {
            return new Promise((resolve, reject) => {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = TAMANHO_FINAL;
                    canvas.height = TAMANHO_FINAL;
                    const ctx = canvas.getContext('2d');

                    // Calcular qual região da imagem ORIGINAL está dentro do quadrado do palco.
                    // O palco tem TAMANHO_PALCO px e mostra a imagem com scale + offset.
                    // sx, sy = pixels da imagem original que ficam no canto sup-esq do palco.
                    const w = estado.img.naturalWidth  * estado.scale;
                    const h = estado.img.naturalHeight * estado.scale;
                    const xVisivel = (w - TAMANHO_PALCO) / 2 - estado.offsetX;
                    const yVisivel = (h - TAMANHO_PALCO) / 2 - estado.offsetY;
                    const sx = xVisivel / estado.scale;
                    const sy = yVisivel / estado.scale;
                    const sSize = TAMANHO_PALCO / estado.scale;

                    ctx.drawImage(estado.img, sx, sy, sSize, sSize, 0, 0, TAMANHO_FINAL, TAMANHO_FINAL);
                    canvas.toBlob((blob) => {
                        if (blob) resolve(blob);
                        else reject(new Error('toBlob() retornou null'));
                    }, 'image/jpeg', 0.9);
                } catch (err) {
                    reject(err);
                }
            });
        }
    }
})();
