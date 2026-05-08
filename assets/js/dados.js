/**
 * TERMINAL DA ORDEM - Modulo de rolagem de dados (multi-dado).
 *
 * Tipos suportados: d4, d6, d8, d10, d12, d20, d100.
 *
 * Regras:
 *   - d20 com atributo N >= 1 : rola N d20 e mantem o MAIOR (Vantagem).
 *   - d20 com atributo N == 0 : rola 2 d20 e mantem o MENOR (Desastre).
 *   - Demais tipos            : rola 1 dado simples.
 *
 * Brilho intenso visual:
 *   - Resultado 1  -> is-desastre (Falha Critica)
 *   - Resultado 20 -> is-critico  (Sucesso Critico)
 *   - Outros valores nao brilham.
 *
 * O resultado eh enviado para /rolagem/api.php (URL via data-api do form).
 */

(() => {
    'use strict';

    /** Mapa do numero maximo de cada dado. */
    const DADOS = {
        d4:   4,
        d6:   6,
        d8:   8,
        d10:  10,
        d12:  12,
        d20:  20,
        d100: 100,
    };

    /**
     * Calibragem MANUAL de volume por arquivo.
     *
     * Os 3 .mp3 foram gravados com volumes diferentes (peak/RMS distintos).
     * Como nao reencodamos os arquivos, ajustamos o volume de reproducao
     * em JS para deixar tudo na mesma "loudness perceptual".
     *
     * Valores entre 0.0 e 1.0. Ajuste se algum som ainda parecer alto/baixo:
     *  - rolagem  (som padrao)         — base de comparacao
     *  - multipla (2-4 dados)          — ajustado para empilhar com rolagem
     *  - muitos   (5+ dados)           — ajustado para empilhar com os 2 acima
     *
     * Quando varios sons tocam JUNTOS, valores menores evitam saturacao
     * (clipping) na mistura.
     */
    const VOLUMES = {
        rolagem:  0.55,
        multipla: 0.50,
        muitos:   0.50,
    };

    /**
     * FadeOut bem rapido: o som termina no INSTANTE em que o numero
     * aparece na tela (ticker dura ~750ms; este fade encerra em 80ms).
     */
    const FADE_OUT_MS = 80;

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('form-rolagem');
        if (!form) return;

        const elValor   = document.getElementById('rolador-valor');
        const elRotulo  = document.getElementById('rolador-rotulo');
        const elDados   = document.getElementById('rolador-dados');
        const elSvg     = document.getElementById('dado-svg');
        const elTipoSvg = document.getElementById('dado-svg-label');
        const formasSvg = elSvg ? elSvg.querySelectorAll('.dado-svg__forma') : [];
        const campoAtr  = document.getElementById('campo-atributo');
        const csrfToken = form.dataset.csrf || '';
        const apiUrl    = form.dataset.api  || '/rolagem/api.php';

        // 3 sons que se EMPILHAM conforme a quantidade de dados:
        //   1 dado    → som_para_as_rolagens (sozinho)
        //   2-4 dados → som_para_as_rolagens + som_para_rolagem_multipla
        //   5+ dados  → som_para_as_rolagens + som_para_rolagem_multipla
        //                                    + som_para_rolagem_com_muitos_dados
        // Empilhar = sensacao de varios dados rolando juntos.
        const audioRolagem        = criarAudio(form.dataset.audioRolagem);
        const audioRolagemMulti   = criarAudio(form.dataset.audioRolagemMultipla);
        const audioRolagemMuitos  = criarAudio(form.dataset.audioRolagemMuitos);
        let   audiosAtivos        = [];   // array dos Audios tocando agora

        // Duracao aproximada da animacao visual (ticker 750ms + folga ate o "settle"):
        const DURACAO_ANIMACAO_MS = 1100;

        // Cooldown do botao de invocar — equivale a animacao visual (750ms)
        //   + fadeout do audio (450ms) + pequena folga para variancia de timing.
        // Impede spam de cliques que bagunca audio e exibicao do resultado.
        const botaoSubmit = form.querySelector('button[type="submit"]');
        const COOLDOWN_MS = 1300;

        // Mostra/esconde o campo "atributo" conforme o tipo selecionado
        const radios = form.querySelectorAll('input[name="tipo_dado"]');
        radios.forEach((radio) => {
            radio.addEventListener('change', () => atualizarVisibilidadeAtributo());
        });
        atualizarVisibilidadeAtributo();

        function atualizarVisibilidadeAtributo() {
            const tipo  = obterTipoSelecionado();
            const isD20 = tipo === 'd20';

            // Campo de quantidade SEMPRE visível agora — todos os tipos
            // suportam multi-dado. Apenas a ajuda muda.
            if (campoAtr) {
                campoAtr.classList.remove('is-oculto');
            }

            // Toggle das ajudas contextuais
            form.querySelectorAll('[data-ajuda-d20]').forEach((el) => { el.hidden = !isD20; });
            form.querySelectorAll('[data-ajuda-outros]').forEach((el) => { el.hidden = isD20; });

            // Min/max do input: d20 permite 0 (DESASTRE), demais não
            const inputQtd = form.querySelector('#quantidade');
            if (inputQtd) {
                inputQtd.min = isD20 ? '0' : '1';
                if (!isD20 && parseInt(inputQtd.value, 10) === 0) {
                    inputQtd.value = '1';
                }
            }

            // Atualiza o label dentro do SVG grande
            if (elTipoSvg) {
                elTipoSvg.textContent = tipo;
            }

            // Troca a forma visivel no SVG da direita.
            // BUG conhecido: o atributo HTML `hidden` em <g> SVG nao e respeitado
            // de forma confiavel pelos browsers. Usamos `display="none"` (atributo
            // SVG) + classe CSS de seguranca para garantir o comportamento.
            formasSvg.forEach((forma) => {
                if (forma.dataset.dado === tipo) {
                    forma.removeAttribute('hidden');
                    forma.removeAttribute('display');
                    forma.classList.remove('is-oculta');
                } else {
                    forma.setAttribute('hidden', '');
                    forma.setAttribute('display', 'none');
                    forma.classList.add('is-oculta');
                }
            });
        }

        function obterTipoSelecionado() {
            const checked = form.querySelector('input[name="tipo_dado"]:checked');
            return (checked && DADOS[checked.value]) ? checked.value : 'd20';
        }

        /**
         * Dispara TODOS os sons aplicáveis para a quantidade de dados rolados.
         * Os sons tocam SIMULTANEAMENTE — efeito de cascata de dados batendo.
         *
         *   1 dado    → [rolagem]
         *   2-4 dados → [rolagem, multipla]
         *   5+ dados  → [rolagem, multipla, muitos]
         *
         * Cada audio toca com seu volume calibrado em VOLUMES.* para que
         * a mistura nao sature.
         *
         * Definida AQUI dentro do DOMContentLoaded para ter closure sobre
         * as variaveis audioRolagem* / VOLUMES.
         */
        function tocarAudiosRolagem(quantidade) {
            const camadas = [];
            if (audioRolagem)                            camadas.push([audioRolagem,       VOLUMES.rolagem]);
            if (quantidade >= 2 && audioRolagemMulti)    camadas.push([audioRolagemMulti,  VOLUMES.multipla]);
            if (quantidade >= 5 && audioRolagemMuitos)   camadas.push([audioRolagemMuitos, VOLUMES.muitos]);

            const ativos = [];
            camadas.forEach(([audio, volume]) => {
                try {
                    audio.pause();
                    audio.currentTime = 0;
                    audio.volume = volume;
                    audio.play().catch(() => { /* autoplay bloqueado, ignora */ });
                    ativos.push(audio);
                } catch (e) { /* navegador antigo */ }
            });
            return ativos;
        }

        form.addEventListener('submit', async (evt) => {
            evt.preventDefault();

            // Cooldown guard: se o botao ainda esta desativado da rolagem
            // anterior, ignora o clique (evita spam que bagunca audio/visual).
            if (botaoSubmit && botaoSubmit.disabled) return;

            const dadosForm  = new FormData(form);
            const quemRolou  = (dadosForm.get('quem_rolou') || '').toString().trim();
            const descricao  = (dadosForm.get('descricao')  || '').toString().trim();
            const tipo       = obterTipoSelecionado();
            const isD20      = tipo === 'd20';
            // Quantidade agora se aplica a TODOS os tipos. d20 permite 0 (desastre).
            // Demais tipos: mínimo 1 (sem regra de desastre).
            const qtdRaw     = parseInt(dadosForm.get('quantidade') || '1', 10);
            const quantidade = isD20
                ? Math.max(0, Math.min(10, qtdRaw))
                : Math.max(1, Math.min(10, qtdRaw));

            if (quemRolou === '' || descricao === '') {
                window.alert('Identifique quem esta rolando e o motivo da rolagem.');
                return;
            }

            // Trava o botao pelo tempo da animacao + audio
            if (botaoSubmit) {
                botaoSubmit.disabled = true;
                botaoSubmit.classList.add('is-cooldown');
                window.setTimeout(() => {
                    botaoSubmit.disabled = false;
                    botaoSubmit.classList.remove('is-cooldown');
                }, COOLDOWN_MS);
            }

            // SFX em CAMADAS — os sons disparam juntos para dar a sensacao
            // de varios dados rolando em coro:
            //   1 dado     → 1 som
            //   2-4 dados  → 2 sons sobrepostos
            //   5+ dados   → 3 sons sobrepostos
            // Para d20 atributo=0 (desastre, 2 dados) cai em "2-4".
            audiosAtivos = tocarAudiosRolagem(quantidade === 0 ? 2 : quantidade);

            // Animacao de "girando"
            elSvg?.classList.add('is-rolando');
            elValor.textContent  = '?';
            elValor.classList.remove('is-critico', 'is-desastre');
            elRotulo.textContent = 'CONSULTANDO O OUTRO LADO...';
            elRotulo.classList.remove('is-critico', 'is-desastre');
            elDados.innerHTML    = '';

            // Pequeno "ticker" durante o giro: o numero central pula entre faces
            const ticker = setInterval(() => {
                elValor.textContent = String(Math.floor(Math.random() * DADOS[tipo]) + 1);
            }, 70);

            await delay(750);
            clearInterval(ticker);

            const resultado = rolar(tipo, quantidade);
            const { rolagens, resultadoFinal, modo, ehCritico, ehDesastre } = resultado;

            elSvg?.classList.remove('is-rolando');
            elValor.textContent = String(resultadoFinal);
            if (ehCritico)  { elValor.classList.add('is-critico');  elRotulo.classList.add('is-critico');  }
            if (ehDesastre) { elValor.classList.add('is-desastre'); elRotulo.classList.add('is-desastre'); }

            elRotulo.textContent = textoRotulo(tipo, modo, ehCritico, ehDesastre, rolagens, resultadoFinal);
            renderizarMiniDados(elDados, rolagens, resultadoFinal, modo);

            // Cortar TODOS os audios ativos no INSTANTE em que o numero
            // aparece — fadeOut bem rapido (FADE_OUT_MS) evita "click"
            // ao parar abruptamente, mas mantem o efeito de "som acaba
            // junto com o resultado".
            audiosAtivos.forEach((a) => {
                if (a && !a.paused) fadeOutAudio(a, FADE_OUT_MS);
            });
            audiosAtivos = [];

            // Persistencia server-side
            try {
                const resp = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        csrf_token:        csrfToken,
                        quem_rolou:        quemRolou,
                        descricao:         descricao,
                        tipo_dado:         tipo,
                        quantidade_dados:  String(quantidade),
                        resultados_brutos: JSON.stringify(rolagens),
                    }),
                });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
            } catch (err) {
                console.error('Falha ao salvar rolagem:', err);
                elRotulo.textContent +=
                    ' // [AVISO] Resultado nao foi gravado no log: ' + err.message;
            }
        });
    });

    /**
     * Lança dados conforme o tipo e a regra aplicável.
     *
     * d20:
     *   - quantidade = 0 → DESASTRE: 2d20, mantém o MENOR
     *   - quantidade = 1 → NORMAL: 1d20
     *   - quantidade ≥ 2 → VANTAGEM: NdN, mantém o MAIOR
     *   resultadoFinal = valor único escolhido
     *
     * Outros tipos (d4, d6, d8, d10, d12, d100):
     *   - quantidade = 1 → SIMPLES: 1 dado, valor exibido
     *   - quantidade ≥ 2 → MÚLTIPLA: N dados independentes,
     *     TODOS exibidos na UI, resultadoFinal = SOMA
     */
    function rolar(tipo, quantidade) {
        const lados = DADOS[tipo] || 20;
        let rolagens, resultadoFinal, modo;

        if (tipo === 'd20') {
            if (quantidade === 0) {
                rolagens = [rolarDado(lados), rolarDado(lados)];
                resultadoFinal = Math.min(...rolagens);
                modo = 'desastre';
            } else {
                const n = Math.max(1, Math.min(10, quantidade));
                rolagens = Array.from({ length: n }, () => rolarDado(lados));
                resultadoFinal = Math.max(...rolagens);
                modo = n === 1 ? 'normal' : 'vantagem';
            }
        } else {
            const n = Math.max(1, Math.min(10, quantidade));
            rolagens = Array.from({ length: n }, () => rolarDado(lados));
            if (n === 1) {
                resultadoFinal = rolagens[0];
                modo = 'simples';
            } else {
                resultadoFinal = rolagens.reduce((s, v) => s + v, 0);
                modo = 'multipla';
            }
        }

        // Brilho APENAS em 1 (Falha) e 20 (Sucesso) NO d20.
        // Outros tipos de dado e multi-dado não acionam crítico nem desastre.
        const ehD20 = tipo === 'd20';
        return {
            rolagens,
            resultadoFinal,
            modo,
            ehCritico:  ehD20 && resultadoFinal === 20,
            ehDesastre: ehD20 && resultadoFinal === 1,
        };
    }

    function rolarDado(lados) {
        return Math.floor(Math.random() * lados) + 1;
    }

    function delay(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    function textoRotulo(tipo, modo, ehCritico, ehDesastre, rolagens, resultadoFinal) {
        if (ehCritico)  return '>> SUCESSO CRÍTICO! O Outro Lado responde com clareza.';
        if (ehDesastre) return '>> FALHA CRÍTICA. As entidades sussurram com escárnio.';
        if (modo === 'desastre') return '>> DESASTRE. Resultado mantido: menor dado.';
        if (modo === 'vantagem') return '>> VANTAGEM. Resultado mantido: maior dado.';
        if (modo === 'simples')  return '>> ' + tipo.toUpperCase() + ' — rolagem simples confirmada.';
        if (modo === 'multipla') {
            return '>> ' + rolagens.length + tipo.toUpperCase() + ' — '
                 + rolagens.join(' + ') + ' = ' + resultadoFinal;
        }
        return '>> ROLAGEM CONFIRMADA.';
    }

    function renderizarMiniDados(container, rolagens, resultadoFinal, modo) {
        container.innerHTML = '';
        // Em multi não-d20 (modo='multipla'), TODOS os valores valem igual —
        // não há "vencedor". Renderizamos todos com a mesma ênfase.
        const exibirVencedor = modo === 'normal' || modo === 'vantagem' || modo === 'desastre';
        let jaDestacado = false;
        rolagens.forEach((valor) => {
            const el = document.createElement('span');
            el.className = 'dado-mini';
            el.textContent = String(valor);
            if (exibirVencedor) {
                if (!jaDestacado && valor === resultadoFinal) {
                    el.classList.add('is-vencedor');
                    jaDestacado = true;
                } else {
                    el.classList.add('is-perdedor');
                }
            } else {
                el.classList.add('is-igual');  // todos pesam igual
            }
            container.appendChild(el);
        });
    }

    /**
     * Cria um Audio() pré-carregado a partir de uma URL (ou null).
     * Stateless — pode ficar no escopo da IIFE.
     */
    function criarAudio(src) {
        if (!src) return null;
        const a = new Audio(src);
        a.preload = 'auto';
        a.volume  = 0.55;
        return a;
    }

    /**
     * Fade-out linear do volume usando requestAnimationFrame (suave, sem clique).
     * Stateless — pode ficar no escopo da IIFE.
     */
    function fadeOutAudio(audioEl, duracao) {
        const volumeInicial = audioEl.volume;
        const inicio = performance.now();

        function passo(agora) {
            const t = (agora - inicio) / duracao;
            if (t >= 1) {
                audioEl.pause();
                audioEl.currentTime = 0;
                audioEl.volume = volumeInicial;
                return;
            }
            audioEl.volume = volumeInicial * (1 - t);
            window.requestAnimationFrame(passo);
        }
        window.requestAnimationFrame(passo);
    }
})();
