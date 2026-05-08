/**
 * TERMINAL DA ORDEM - Ficha de Agente (Fase 4)
 *
 * Comportamentos client-side:
 *   1. BARRAS PV/SAN/PE: atualizam visualmente em tempo real conforme
 *      o usuário muda os valores numéricos (atual e máximo).
 *   2. ATAQUES: cada linha calcula seu TOTAL em tempo real
 *      [Atributo] + [Bônus arma] + [Bônus extra]. Reage a mudanças
 *      nos atributos da seção 03.
 *   3. INVENTÁRIO: calcula soma de espaços (espaços × quantidade) em tempo real.
 *   4. LISTAS DINÂMICAS: adicionar/remover linhas em ataques, inventário e rituais.
 *   5. PERÍCIAS: cor da linha varia com o grau selecionado.
 *
 * Sai cedo se o form de agente não estiver presente (script é carregado em
 * todas as páginas).
 */

(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-form-agente]');
        if (!form) return;

        inicializarBarras(form);
        inicializarAtaques(form);
        inicializarInventario(form);
        inicializarPericias(form);
        inicializarListasDinamicas(form);
    });

    /* ============================================================
     * BARRAS DE VIDA / SANIDADE / ESFORÇO
     * ============================================================ */
    function inicializarBarras(form) {
        form.querySelectorAll('[data-barra]').forEach((bloco) => {
            const inputAtual  = bloco.querySelector('[data-barra-atual]');
            const inputMaximo = bloco.querySelector('[data-barra-maximo]');
            const preench     = bloco.querySelector('.barra-ficha__preenchimento');
            if (!inputAtual || !inputMaximo || !preench) return;

            const atualizar = () => {
                const max = Math.max(0, parseInt(inputMaximo.value || '0', 10));
                let atual = Math.max(0, parseInt(inputAtual.value || '0', 10));
                if (max > 0 && atual > max) {
                    atual = max;
                    inputAtual.value = max;  // não permite atual > maximo
                }
                const pct = max > 0 ? Math.min(100, (atual / max) * 100) : 0;
                preench.style.width = pct + '%';
            };
            inputAtual.addEventListener('input', atualizar);
            inputMaximo.addEventListener('input', atualizar);
            atualizar();
        });
    }

    /* ============================================================
     * ATAQUES — cálculo automático do total
     * ============================================================ */
    function inicializarAtaques(form) {
        const recalcularTodos = () => {
            form.querySelectorAll('.ataque-linha').forEach(recalcularLinhaAtaque);
        };

        // Mudanças nos atributos disparam recálculo de TODOS os ataques
        form.querySelectorAll('[data-atributo]').forEach((input) => {
            input.addEventListener('input', recalcularTodos);
        });

        // Mudanças dentro de cada linha disparam recálculo só dela
        form.addEventListener('input', (e) => {
            if (e.target.matches('[data-ataque-atributo], [data-ataque-bonus-arma], [data-ataque-bonus-extra]')) {
                const linha = e.target.closest('.ataque-linha');
                if (linha) recalcularLinhaAtaque(linha);
            }
        });

        recalcularTodos();
    }

    function recalcularLinhaAtaque(linha) {
        const form       = linha.closest('form');
        const selAtrib   = linha.querySelector('[data-ataque-atributo]');
        const inputArma  = linha.querySelector('[data-ataque-bonus-arma]');
        const inputExtra = linha.querySelector('[data-ataque-bonus-extra]');
        const out        = linha.querySelector('[data-ataque-total]');
        if (!selAtrib || !out) return;

        const atribInput = form.querySelector(`[data-atributo="${selAtrib.value}"]`);
        const valAtrib = atribInput ? parseInt(atribInput.value || '0', 10) : 0;
        const valArma  = inputArma  ? parseInt(inputArma.value  || '0', 10) : 0;
        const valExtra = inputExtra ? parseInt(inputExtra.value || '0', 10) : 0;
        const total = valAtrib + valArma + valExtra;
        out.textContent = (total >= 0 ? '+' : '') + total;
        out.classList.toggle('is-positivo', total > 0);
        out.classList.toggle('is-negativo', total < 0);
    }

    /* ============================================================
     * INVENTÁRIO — total de espaços ocupados
     * ============================================================ */
    function inicializarInventario(form) {
        const total = form.querySelector('[data-inventario-total]');
        if (!total) return;

        const recalcular = () => {
            let soma = 0;
            form.querySelectorAll('.inventario-linha').forEach((linha) => {
                const esp = parseFloat(linha.querySelector('[data-inv-espacos]')?.value || '0');
                const qtd = parseInt(linha.querySelector('[data-inv-quantidade]')?.value || '0', 10);
                if (!isNaN(esp) && !isNaN(qtd)) soma += esp * qtd;
            });
            total.textContent = soma.toFixed(2);
        };

        form.addEventListener('input', (e) => {
            if (e.target.matches('[data-inv-espacos], [data-inv-quantidade]')) {
                recalcular();
            }
        });
        recalcular();
    }

    /* ============================================================
     * PERÍCIAS — cor visual conforme grau selecionado
     * ============================================================ */
    function inicializarPericias(form) {
        form.querySelectorAll('[data-pericia-row]').forEach((row) => {
            const select = row.querySelector('[data-pericia-grau]');
            if (!select) return;
            const atualizar = () => {
                row.classList.remove(
                    'pericia--grau-destreinado',
                    'pericia--grau-treinado',
                    'pericia--grau-veterano',
                    'pericia--grau-especialista'
                );
                row.classList.add('pericia--grau-' + select.value.toLowerCase());
            };
            select.addEventListener('change', atualizar);
        });
    }

    /* ============================================================
     * LISTAS DINÂMICAS — adicionar/remover linhas em ataques/inventario/rituais
     *
     * Cada lista tem um <template id="tpl-<tipo>"> com __i__ como placeholder
     * de índice. Inserções incrementam um contador por tipo.
     * ============================================================ */
    function inicializarListasDinamicas(form) {
        const contadores = {
            ataque:    contarLinhas('ataques'),
            inventario: contarLinhas('inventario'),
            ritual:    contarLinhas('rituais'),
        };

        form.addEventListener('click', (e) => {
            // Adicionar
            const botaoAdicionar = e.target.closest('[data-adicionar]');
            if (botaoAdicionar) {
                e.preventDefault();
                const tipo = botaoAdicionar.dataset.adicionar;
                adicionarLinha(tipo, contadores[tipo]++, form);
                return;
            }

            // Remover
            const botaoRemover = e.target.closest('[data-remover-item]');
            if (botaoRemover) {
                e.preventDefault();
                const linha = botaoRemover.closest('[data-item-row]');
                if (linha) {
                    linha.remove();
                    // Recalcular totais que dependem de linhas existentes
                    form.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        });

        // Para rituais: ao mudar o select de elemento, atualizar a classe da linha
        form.addEventListener('change', (e) => {
            if (e.target.matches('[data-ritual-elemento-select]')) {
                const linha = e.target.closest('.ritual-linha');
                if (!linha) return;
                ['sangue', 'morte', 'conhecimento', 'energia'].forEach((el) => {
                    linha.classList.remove('ritual-linha--' + el);
                });
                linha.classList.add('ritual-linha--' + e.target.value.toLowerCase());
            }
        });

        function contarLinhas(nomePlural) {
            return form.querySelectorAll('#lista-' + nomePlural + ' [data-item-row]').length;
        }
    }

    function adicionarLinha(tipo, indice, form) {
        const idTemplate = 'tpl-' + tipo;
        const tpl = form.querySelector('#' + idTemplate);
        if (!tpl) return;

        // O <template> tem o markup com __i__ como placeholder do índice.
        // Substituímos e inserimos no container apropriado.
        const html = tpl.innerHTML.replaceAll('__i__', String(indice));

        const containerId = {
            ataque:    'lista-ataques',
            inventario: 'lista-inventario',
            ritual:    'lista-rituais',
        }[tipo];
        const container = form.querySelector('#' + containerId);
        if (!container) return;

        container.insertAdjacentHTML('beforeend', html);

        // Foca no nome do novo item
        const novaLinha = container.lastElementChild;
        const inputFoco = novaLinha?.querySelector('input[type="text"]');
        inputFoco?.focus();

        // Recalcular tudo (atributos, totais)
        form.dispatchEvent(new Event('input', { bubbles: true }));
    }
})();
