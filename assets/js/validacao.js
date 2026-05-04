/**
 * TERMINAL DA ORDEM - Comportamentos do cliente.
 *
 * Responsabilidades:
 *   - Validar formulários (campos vazios e tamanhos mínimos) antes do POST.
 *   - Confirmar exclusões.
 *   - Atualizar contadores ao vivo de caracteres.
 *   - Avisar sobre módulos ainda não implementados.
 */

(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        inicializarValidacao();
        inicializarConfirmacoes();
        inicializarContadores();
        inicializarModulosEmBreve();
    });

    /**
     * Liga validação a cada formulário marcado com [data-validar-formulario].
     */
    function inicializarValidacao() {
        const formularios = document.querySelectorAll('[data-validar-formulario]');
        formularios.forEach((form) => {
            form.addEventListener('submit', (evt) => {
                const erros = validarFormulario(form);
                if (erros.length > 0) {
                    evt.preventDefault();
                    erros[0].campo.focus();
                }
            });

            form.querySelectorAll('input, textarea').forEach((campo) => {
                campo.addEventListener('blur', () => validarCampo(campo));
                campo.addEventListener('input', () => limparErroCampo(campo));
            });
        });
    }

    /**
     * Valida todos os campos required do formulário.
     */
    function validarFormulario(form) {
        const erros = [];
        const campos = form.querySelectorAll('input[required], textarea[required]');
        campos.forEach((campo) => {
            const erro = validarCampo(campo);
            if (erro) erros.push({ campo, erro });
        });
        return erros;
    }

    /**
     * Valida um único campo. Retorna a mensagem de erro ou null.
     */
    function validarCampo(campo) {
        const valor = (campo.value || '').trim();
        const min = parseInt(campo.getAttribute('minlength') || '0', 10);
        let mensagem = '';

        if (campo.hasAttribute('required') && valor === '') {
            mensagem = `O campo "${rotuloDe(campo)}" nao pode ficar vazio.`;
        } else if (min > 0 && valor.length < min) {
            mensagem = `O campo "${rotuloDe(campo)}" precisa de ao menos ${min} caracteres.`;
        }

        if (mensagem) {
            marcarCampoInvalido(campo, mensagem);
            return mensagem;
        }
        limparErroCampo(campo);
        return null;
    }

    function rotuloDe(campo) {
        const lbl = document.querySelector(`label[for="${campo.id}"]`);
        if (!lbl) return campo.name || campo.id;
        return lbl.textContent
            .replace(/\s*\*\s*$/, '')
            .replace(/^\s*\d+\.\s*/, '')
            .trim();
    }

    function marcarCampoInvalido(campo, mensagem) {
        const wrapper = campo.closest('.campo');
        if (wrapper) wrapper.classList.add('campo--invalido');
        campo.setAttribute('aria-invalid', 'true');
        const alvo = document.querySelector(`[data-erro-para="${campo.name}"]`);
        if (alvo) alvo.textContent = mensagem;
    }

    function limparErroCampo(campo) {
        const wrapper = campo.closest('.campo');
        if (wrapper) wrapper.classList.remove('campo--invalido');
        campo.setAttribute('aria-invalid', 'false');
        const alvo = document.querySelector(`[data-erro-para="${campo.name}"]`);
        if (alvo) alvo.textContent = '';
    }

    /**
     * Pede confirmação antes de submeter formulários com [data-confirmar].
     */
    function inicializarConfirmacoes() {
        document.querySelectorAll('form[data-confirmar]').forEach((form) => {
            form.addEventListener('submit', (evt) => {
                const msg = form.getAttribute('data-confirmar') || 'Confirma a operacao?';
                if (!window.confirm(msg)) evt.preventDefault();
            });
        });
    }

    /**
     * Atualiza ao vivo elementos [data-contador-de="<nome-do-campo>"].
     */
    function inicializarContadores() {
        document.querySelectorAll('[data-contador-de]').forEach((contador) => {
            const nome = contador.getAttribute('data-contador-de');
            const alvo = document.querySelector(`[name="${nome}"]`);
            if (!alvo) return;
            const atualizar = () => { contador.textContent = String(alvo.value.length); };
            alvo.addEventListener('input', atualizar);
            atualizar();
        });
    }

    /**
     * Bloqueia links de módulos ainda em desenvolvimento.
     */
    function inicializarModulosEmBreve() {
        document.querySelectorAll('[data-modulo-em-breve]').forEach((link) => {
            link.addEventListener('click', (evt) => {
                evt.preventDefault();
                window.alert('Modulo em desenvolvimento. Em breve estara disponivel no Terminal.');
            });
        });
    }
})();
