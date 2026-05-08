-- =====================================================================
-- TERMINAL DA ORDEM - Migration 003: Reforma VTT
-- Adiciona suporte a Campanhas + Agentes (fichas de jogador completas) +
-- relacionamentos auxiliares (perícias, ataques, inventário, rituais).
--
-- Compatível: MariaDB 10.4+ (XAMPP) e MySQL 8.x.
-- Aplicação: phpMyAdmin → banco clarividencia_rpg → Importar.
-- =====================================================================

USE clarividencia_rpg;

-- =====================================================================
-- TABELAS NOVAS
-- =====================================================================

-- Uma "campanha" agrega uma sessão de RPG inteira: capa, descrição,
-- agentes/jogadores vinculados, NPCs e criaturas em jogo.
CREATE TABLE IF NOT EXISTS campanhas (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(140) NOT NULL,
    sistema       VARCHAR(60)  NOT NULL DEFAULT 'Ordem Paranormal',
    descricao     TEXT         NOT NULL,
    capa_arquivo  VARCHAR(160) NULL,
    criado_em     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_campanhas_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ficha de agente (personagem jogador). Mantém todos os blocos canônicos
-- de Ordem Paranormal: Identidade, Barras (PV/SAN/PE), Atributos (5),
-- Defesa, Narrativa. Perícias, Ataques, Inventário e Rituais ficam em
-- tabelas-filhas próprias (1:N).
CREATE TABLE IF NOT EXISTS agentes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campanha_id     INT UNSIGNED NULL,

    -- Identidade
    nome            VARCHAR(120) NOT NULL,
    jogador         VARCHAR(120) NULL,
    origem          VARCHAR(60)  NULL,
    classe          ENUM('Combatente','Especialista','Ocultista')
                                  NOT NULL DEFAULT 'Combatente',
    nex             TINYINT UNSIGNED NOT NULL DEFAULT 5,
    foto_arquivo    VARCHAR(160) NULL,

    -- Barras dinâmicas (atual / máximo)
    pv_atual        SMALLINT NOT NULL DEFAULT 8,
    pv_maximo       SMALLINT NOT NULL DEFAULT 8,
    san_atual       SMALLINT NOT NULL DEFAULT 4,
    san_maximo      SMALLINT NOT NULL DEFAULT 4,
    pe_atual        SMALLINT NOT NULL DEFAULT 2,
    pe_maximo       SMALLINT NOT NULL DEFAULT 2,

    -- Atributos canônicos OP (FOR / AGI / INT / VIG / PRE)
    forca           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    agilidade       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    intelecto       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    vigor           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    presenca        TINYINT UNSIGNED NOT NULL DEFAULT 1,

    -- Defesa & Resistências
    pe_por_turno    TINYINT UNSIGNED NOT NULL DEFAULT 2,
    deslocamento    TINYINT UNSIGNED NOT NULL DEFAULT 9,
    defesa          SMALLINT NOT NULL DEFAULT 10,
    resistencias    TEXT NULL,
    proficiencias   TEXT NULL,

    -- Narrativa
    aparencia       TEXT NULL,
    personalidade   TEXT NULL,
    historico       TEXT NULL,
    objetivos       TEXT NULL,

    -- Auditoria
    criado_em       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_agentes_campanha FOREIGN KEY (campanha_id)
        REFERENCES campanhas(id) ON DELETE SET NULL,
    INDEX idx_agentes_nome (nome),
    INDEX idx_agentes_campanha (campanha_id),
    INDEX idx_agentes_classe (classe),
    CONSTRAINT chk_agentes_pv  CHECK (pv_atual  >= 0 AND pv_atual  <= pv_maximo),
    CONSTRAINT chk_agentes_san CHECK (san_atual >= 0 AND san_atual <= san_maximo),
    CONSTRAINT chk_agentes_pe  CHECK (pe_atual  >= 0 AND pe_atual  <= pe_maximo),
    CONSTRAINT chk_agentes_atrib CHECK (
        forca BETWEEN 0 AND 6 AND agilidade BETWEEN 0 AND 6
        AND intelecto BETWEEN 0 AND 6 AND vigor BETWEEN 0 AND 6
        AND presenca BETWEEN 0 AND 6
    ),
    CONSTRAINT chk_agentes_nex   CHECK (nex BETWEEN 1 AND 99)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Perícias do agente (1 linha por perícia). Mantemos um catálogo enxuto
-- como VARCHAR para flexibilidade — a UI controla os valores válidos.
CREATE TABLE IF NOT EXISTS agente_pericias (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agente_id    INT UNSIGNED NOT NULL,
    pericia      VARCHAR(40) NOT NULL,
    grau         ENUM('Destreinado','Treinado','Veterano','Especialista')
                              NOT NULL DEFAULT 'Destreinado',
    bonus_extra  TINYINT NOT NULL DEFAULT 0,
    UNIQUE KEY uk_agente_pericia (agente_id, pericia),
    CONSTRAINT fk_pericia_agente FOREIGN KEY (agente_id)
        REFERENCES agentes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ataques do agente. O cálculo "atributo + bônus_arma + bônus_extra"
-- é feito na UI/PHP a partir do agente_id, não na coluna.
CREATE TABLE IF NOT EXISTS agente_ataques (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agente_id       INT UNSIGNED NOT NULL,
    nome            VARCHAR(80) NOT NULL,
    atributo_base   ENUM('forca','agilidade','intelecto','vigor','presenca')
                                NOT NULL DEFAULT 'forca',
    bonus_arma      TINYINT NOT NULL DEFAULT 0,
    bonus_extra     TINYINT NOT NULL DEFAULT 0,
    dano            VARCHAR(40) NOT NULL DEFAULT '1d4',
    tipo_dano       VARCHAR(40) NULL,
    descricao       TEXT NULL,
    ordem           SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT fk_ataque_agente FOREIGN KEY (agente_id)
        REFERENCES agentes(id) ON DELETE CASCADE,
    INDEX idx_ataque_agente_ordem (agente_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventário (espaços em vez de peso, conforme regras OP).
CREATE TABLE IF NOT EXISTS agente_inventario (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agente_id   INT UNSIGNED NOT NULL,
    nome        VARCHAR(120) NOT NULL,
    descricao   TEXT NULL,
    categoria   VARCHAR(40) NULL,
    espacos     DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    quantidade  INT UNSIGNED NOT NULL DEFAULT 1,
    equipado    BOOLEAN NOT NULL DEFAULT FALSE,
    ordem       SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT fk_inv_agente FOREIGN KEY (agente_id)
        REFERENCES agentes(id) ON DELETE CASCADE,
    INDEX idx_inv_agente_ordem (agente_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rituais conhecidos (1..5 círculos / 4 elementos canônicos).
CREATE TABLE IF NOT EXISTS agente_rituais (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agente_id   INT UNSIGNED NOT NULL,
    nome        VARCHAR(120) NOT NULL,
    circulo     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    elemento    ENUM('Sangue','Morte','Conhecimento','Energia') NOT NULL,
    custo_pe    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    descricao   TEXT NULL,
    ordem       SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT fk_rit_agente FOREIGN KEY (agente_id)
        REFERENCES agentes(id) ON DELETE CASCADE,
    INDEX idx_rit_agente_ordem (agente_id, ordem),
    CONSTRAINT chk_rit_circulo CHECK (circulo BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- ALTER tabelas existentes — agora podem pertencer a uma campanha e
-- ter foto. Use IF NOT EXISTS (MariaDB) para idempotência.
-- =====================================================================
ALTER TABLE npcs
    ADD COLUMN IF NOT EXISTS campanha_id  INT UNSIGNED NULL AFTER id,
    ADD COLUMN IF NOT EXISTS foto_arquivo VARCHAR(160) NULL AFTER atitude;

-- FK e índice — criar separadamente; falhará silenciosamente se já existirem
-- (re-rodar a migration nesse caso é seguro).
ALTER TABLE npcs
    ADD CONSTRAINT fk_npc_campanha FOREIGN KEY (campanha_id)
        REFERENCES campanhas(id) ON DELETE SET NULL;
ALTER TABLE npcs
    ADD INDEX idx_npcs_campanha (campanha_id);

ALTER TABLE criaturas
    ADD COLUMN IF NOT EXISTS campanha_id  INT UNSIGNED NULL AFTER id,
    ADD COLUMN IF NOT EXISTS foto_arquivo VARCHAR(160) NULL AFTER elemento;

ALTER TABLE criaturas
    ADD CONSTRAINT fk_criatura_campanha FOREIGN KEY (campanha_id)
        REFERENCES campanhas(id) ON DELETE SET NULL;
ALTER TABLE criaturas
    ADD INDEX idx_criaturas_campanha (campanha_id);

-- =====================================================================
-- SEED opcional para testar UI imediatamente
-- =====================================================================
INSERT IGNORE INTO campanhas (id, nome, descricao) VALUES
    (1, 'O Eclipse de Mil Olhos',
     'Campanha noir-investigativa ambientada em São Paulo de 1998. Os agentes investigam desaparecimentos ligados ao Outro Lado.');
