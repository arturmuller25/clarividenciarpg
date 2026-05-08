-- =====================================================================
-- TERMINAL DA ORDEM - Migration 004
-- Aumenta a capacidade de log_rolagens.resultado_final para suportar
-- somas de rolagens multi-dado em tipos não-d20.
--
-- Por quê?
--   Antes: resultado_final TINYINT UNSIGNED (max 255) com CHECK 1..100.
--   Agora: rolagens como 10d100 podem somar até 1000 — não cabe em
--          TINYINT, e o CHECK bloquearia.
--
-- Como aplicar:
--   phpMyAdmin → banco clarividencia_rpg → aba Importar → este arquivo.
--
-- Compatível: MariaDB 10.2.1+ (XAMPP atual usa 10.4+) e MySQL 8.0.16+.
-- =====================================================================

USE clarividencia_rpg;

-- 1) Remover o CHECK antigo. DROP CONSTRAINT funciona em MariaDB 10.2+.
ALTER TABLE log_rolagens DROP CONSTRAINT chk_log_resultado;

-- 2) Aumentar a coluna para SMALLINT UNSIGNED (até 65535). Suficiente
--    para qualquer combinação realista (10d100 = 1000 << 65535).
ALTER TABLE log_rolagens
    MODIFY COLUMN resultado_final SMALLINT UNSIGNED NOT NULL;

-- 3) Recriar o CHECK com o novo limite. 2000 cobre todos os casos
--    de rolagens RPG práticas (10d200 hipotético = 2000).
ALTER TABLE log_rolagens
    ADD CONSTRAINT chk_log_resultado CHECK (resultado_final BETWEEN 1 AND 2000);
