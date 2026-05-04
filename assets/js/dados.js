/**
 * TERMINAL DA ORDEM - Módulo de rolagem de dados.
 *
 * Regras canônicas (Ordem Paranormal):
 *   - Atributo N >= 1 : rola N d20 e mantém o MAIOR.
 *   - Atributo N == 0 : rola 2 d20 e mantém o MENOR  (Desastre).
 *   - 20 natural = Crítico (destaque visual).
 *   - 1 natural com atributo 0 = Falha catastrófica adicional.
 *
 * O resultado final é POSTado em /rolagem/api.php que persiste no log_rolagens.
 */

(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('form-rolagem');
        if (!form) return;

        const elValor   = document.getElementById('rolador-valor');
        const elRotulo  = document.getElementById('rolador-rotulo');
        const elDados   = document.getElementById('rolador-dados');
        const elSvg     = document.getElementById('dado-svg');
        const csrfToken = form.dataset.csrf || '';

        form.addEventListener('submit', async (evt) => {
            evt.preventDefault();

            const dadosForm   = new FormData(form);
            const quemRolou   = (dadosForm.get('quem_rolou') || '').toString().trim();
            const descricao   = (dadosForm.get('descricao')  || '').toString().trim();
            const quantidade  = Math.max(0, Math.min(10, parseInt(dadosForm.get('quantidade') || '1', 10)));

            if (quemRolou === '' || descricao === '') {
                window.alert('Identifique quem esta rolando e o motivo da rolagem.');
                return;
            }

            // Animação de "girando"
            elSvg?.classList.add('is-rolando');
            elValor.textContent  = '?';
            elValor.classList.remove('is-critico', 'is-desastre');
            elRotulo.textContent = 'CONSULTANDO O OUTRO LADO...';
            elRotulo.classList.remove('is-critico', 'is-desastre');
            elDados.innerHTML    = '';

            await delay(550);

            const { rolagens, resultadoFinal, ehCritico, ehDesastre, modo } =
                rolarOrdemParanormal(quantidade);

            elSvg?.classList.remove('is-rolando');
            elValor.textContent = String(resultadoFinal);
            if (ehCritico)  { elValor.classList.add('is-critico');  elRotulo.classList.add('is-critico');  }
            if (ehDesastre) { elValor.classList.add('is-desastre'); elRotulo.classList.add('is-desastre'); }

            elRotulo.textContent = textoRotulo(modo, ehCritico, ehDesastre);
            renderizarMiniDados(elDados, rolagens, resultadoFinal, modo);

            // Persistência server-side
            try {
                const resp = await fetch('/rolagem/api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        csrf_token:        csrfToken,
                        quem_rolou:        quemRolou,
                        descricao:         descricao,
                        quantidade_dados:  String(quantidade),
                        resultados_brutos: JSON.stringify(rolagens),
                        resultado_final:   String(resultadoFinal),
                        eh_critico:        ehCritico  ? '1' : '0',
                        eh_desastre:       ehDesastre ? '1' : '0',
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
     * Lança N d20 e aplica a regra do Ordem Paranormal.
     */
    function rolarOrdemParanormal(quantidade) {
        let rolagens, resultadoFinal, modo;

        if (quantidade === 0) {
            // Desastre: rola 2 e mantém o MENOR
            rolagens = [d20(), d20()];
            resultadoFinal = Math.min(...rolagens);
            modo = 'desastre';
        } else {
            const n = Math.max(1, Math.min(10, quantidade));
            rolagens = Array.from({ length: n }, d20);
            resultadoFinal = Math.max(...rolagens);
            modo = n === 1 ? 'normal' : 'vantagem';
        }

        return {
            rolagens,
            resultadoFinal,
            ehCritico:  resultadoFinal === 20,
            ehDesastre: modo === 'desastre',
            modo,
        };
    }

    function d20() {
        // Math.random() é suficiente para rolagens de jogo (não criptográfico).
        return Math.floor(Math.random() * 20) + 1;
    }

    function delay(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    function textoRotulo(modo, ehCritico, ehDesastre) {
        if (ehCritico)  return '>> CRITICO! O Outro Lado responde com clareza.';
        if (ehDesastre) return '>> DESASTRE. As entidades sussurram com escarnio.';
        if (modo === 'vantagem') return '>> VANTAGEM. Resultado mantido: maior dado.';
        return '>> ROLAGEM CONFIRMADA.';
    }

    function renderizarMiniDados(container, rolagens, resultadoFinal, modo) {
        container.innerHTML = '';
        let jaDestacado = false;
        rolagens.forEach((valor) => {
            const el = document.createElement('span');
            el.className = 'dado-mini';
            el.textContent = String(valor);
            if (!jaDestacado && valor === resultadoFinal) {
                el.classList.add('is-vencedor');
                jaDestacado = true;
            } else {
                el.classList.add('is-perdedor');
            }
            container.appendChild(el);
        });
    }
})();
