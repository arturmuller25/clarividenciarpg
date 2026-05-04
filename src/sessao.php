<?php
declare(strict_types=1);

/**
 * Helpers de sessão, CSRF e mensagens flash.
 * Sistema: Terminal da Ordem - Clarividência Paranormal
 */

/**
 * Inicia a sessão se ainda não estiver ativa.
 */
function iniciarSessao(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ]);
    }
}

/**
 * Gera (ou reaproveita) o token CSRF da sessão atual.
 *
 * @return string Token hexadecimal de 64 caracteres.
 */
function gerarTokenCsrf(): string
{
    iniciarSessao();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida em tempo constante o token CSRF recebido do cliente.
 *
 * @param string|null $token Token enviado via formulário.
 */
function validarTokenCsrf(?string $token): bool
{
    iniciarSessao();
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Registra uma mensagem flash que será exibida no próximo carregamento.
 *
 * @param string $tipo     Categoria visual: sucesso | erro | aviso.
 * @param string $mensagem Texto exibido ao usuário.
 */
function definirFlash(string $tipo, string $mensagem): void
{
    iniciarSessao();
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/**
 * Lê e remove todas as mensagens flash pendentes.
 *
 * @return array<int, array{tipo: string, mensagem: string}>
 */
function consumirFlash(): array
{
    iniciarSessao();
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}
