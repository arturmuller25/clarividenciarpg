/**
 * TERMINAL DA ORDEM - Controle da Splash/Hero Screen.
 *
 * Responsabilidades:
 *   1. Tentar iniciar a Hero automaticamente (animacao + audio sincronizados).
 *   2. Se o navegador bloquear autoplay do audio, mostrar o botao
 *      "Iniciar Ritual" — clicar destrava o audio e dispara a animacao.
 *   3. Apos a duracao da animacao (~4.4s), aplicar fadeOut do painel
 *      e fadeOut suave do audio.
 *
 * Sincronizacao audio<->visual:
 *   O audio comeca no MESMO instante em que .is-rodando entra na Hero
 *   (ou seja, junto com o inicio da rotacao do d20).
 *   Se o audio for mais longo que a animacao, fade-out de 700ms ao final.
 *   Se for mais curto, ele simplesmente termina sozinho (sem prejuizo).
 */

(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const hero = document.querySelector('.hero');
        if (!hero) return;

        const botaoIniciar = hero.querySelector('.hero__iniciar');
        const audioSrc     = hero.dataset.audio || '';
        const duracaoMs    = parseInt(hero.dataset.duracaoMs || '4400', 10);

        let audio = null;
        if (audioSrc) {
            audio = new Audio(audioSrc);
            audio.preload = 'auto';
            audio.volume  = 0.7;
        }

        tentarIniciarRitual();

        async function tentarIniciarRitual() {
            // Sem audio configurado: apenas roda a animacao
            if (!audio) {
                disparar(false);
                return;
            }

            try {
                // Tenta autoplay. Em browsers modernos isso pode rejeitar com
                // NotAllowedError se o usuario nao interagiu ainda com a pagina.
                await audio.play();
                disparar(true);
            } catch (err) {
                // Autoplay bloqueado — mostra botao para usuario destravar com 1 clique.
                exibirBotao();
            }
        }

        function exibirBotao() {
            if (!botaoIniciar) {
                // Sem botao no DOM, fallback: dispara sem audio
                disparar(false);
                return;
            }
            botaoIniciar.hidden = false;
            botaoIniciar.focus({ preventScroll: true });

            botaoIniciar.addEventListener('click', () => {
                botaoIniciar.hidden = true;
                if (audio) {
                    audio.play().catch((err) => {
                        // Se mesmo apos clique falhar (arquivo nao existe?), so loga
                        console.warn('Audio da Hero nao tocou apos clique:', err);
                    });
                }
                disparar(true);
            }, { once: true });
        }

        function disparar(comAudio) {
            // Aplica .is-rodando — CSS dispara TODAS as animacoes
            // (rotacao do d20, faces piscando, revelacao do 20, titulo, subtitulo, aura).
            hero.classList.add('is-rodando');

            // Apos duracao total: fadeOut do painel + fadeOut do audio juntos.
            window.setTimeout(() => {
                hero.classList.add('is-saindo');
                if (comAudio && audio && !audio.paused) {
                    fadeOutAudio(audio, 700);
                }
            }, duracaoMs);

            // Apos o fadeout (900ms da animacao .is-saindo), remove do DOM.
            window.setTimeout(() => {
                hero.style.display = 'none';
                hero.setAttribute('aria-hidden', 'true');
            }, duracaoMs + 950);
        }

        /**
         * Fade-out linear do volume usando requestAnimationFrame (suave).
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
    });
})();
