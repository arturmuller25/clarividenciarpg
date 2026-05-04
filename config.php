<?php
declare(strict_types=1);

/**
 * Terminal da Ordem - Configuração de conexão com o banco.
 *
 * Sistema: Clarividência Paranormal
 * Camada : Persistência (PDO / MySQL)
 *
 * Ajuste as credenciais conforme o ambiente local (XAMPP/Laragon/WAMP).
 */

const DB_HOST    = '127.0.0.1';
const DB_PORT    = 3306;
const DB_NAME    = 'clarividencia_rpg';
const DB_USER    = 'root';
const DB_PASS    = '';
const DB_CHARSET = 'utf8mb4';

/**
 * Retorna uma instância PDO única (lazy singleton) configurada com
 * prepared statements reais e exceções habilitadas.
 *
 * @return PDO Conexão ativa com o MySQL.
 * @throws RuntimeException Se a conexão falhar.
 */
function getConexao(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $opcoes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
    } catch (PDOException $e) {
        throw new RuntimeException(
            '[FALHA NO RITUAL DE CONEXÃO] ' . $e->getMessage(),
            (int) $e->getCode(),
            $e
        );
    }

    return $pdo;
}

/**
 * Higieniza string para exibição segura em HTML (anti-XSS).
 *
 * @param string|null $valor Valor bruto vindo do banco ou do usuário.
 * @return string Valor escapado pronto para impressão.
 */
function escapar(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
