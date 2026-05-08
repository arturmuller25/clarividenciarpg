-- =====================================================================
-- TERMINAL DA ORDEM - Migration 002 (v2 — compatível MariaDB + MySQL)
-- Adiciona suporte a múltiplos tipos de dado (d4..d100) em log_rolagens.
--
-- Por que recriar a tabela em vez de ALTER TABLE?
--   A versão anterior usava `DROP CHECK ...` que falha no MariaDB
--   (XAMPP usa MariaDB, não MySQL). `DROP CONSTRAINT` funcionaria em
--   ambos, mas o nome do CHECK pode variar entre engines.
--   Como log_rolagens armazena apenas histórico de testes e não tem
--   FKs apontando para ela, RECRIAR é o caminho limpo e portável.
--
-- ATENÇÃO: rolagens já registradas serão PERDIDAS.
--          Em desenvolvimento isso é seguro. Em produção, faça backup.
--
-- Como aplicar:
--   1) phpMyAdmin → selecione o banco `clarividencia_rpg`
--   2) Aba "Importar" → escolha este arquivo → "Importar"
-- =====================================================================

USE clarividencia_rpg;

-- 1) Remove a tabela antiga (e o CHECK antigo junto, sem precisar nomeá-lo)
DROP TABLE IF EXISTS log_rolagens;

-- 2) Recria com o schema novo: tipo_dado + resultado_final 1..100
CREATE TABLE log_rolagens (
    id                BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    quem_rolou        VARCHAR(120)     NOT NULL,
    descricao         VARCHAR(160)     NOT NULL,
    quantidade_dados  TINYINT UNSIGNED NOT NULL,
    tipo_dado         ENUM('d4','d6','d8','d10','d12','d20','d100')
                                       NOT NULL DEFAULT 'd20',
    resultados_brutos JSON             NOT NULL,
    resultado_final   TINYINT UNSIGNED NOT NULL,
    eh_critico        BOOLEAN          NOT NULL DEFAULT FALSE,
    eh_desastre       BOOLEAN          NOT NULL DEFAULT FALSE,
    rolado_em         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_rolado_em (rolado_em),
    INDEX idx_log_quem      (quem_rolou),
    INDEX idx_log_tipo_dado (tipo_dado),
    CONSTRAINT chk_log_resultado CHECK (resultado_final BETWEEN 1 AND 100),
    CONSTRAINT chk_log_qtd       CHECK (quantidade_dados BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
