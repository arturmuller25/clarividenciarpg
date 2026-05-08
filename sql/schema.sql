-- =====================================================================
-- TERMINAL DA ORDEM - Esquema do Banco de Dados
-- Sistema : Clarividência Paranormal RPG
-- Engine  : InnoDB | Charset: utf8mb4 (suporta emoji e acentuação)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS clarividencia_rpg
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE clarividencia_rpg;

-- ---------------------------------------------------------------------
-- campanhas - agregador de uma sessão completa de RPG.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS campanhas;
CREATE TABLE campanhas (
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

-- ---------------------------------------------------------------------
-- npcs - Personagens não-jogáveis (aliados, contatos, civis, antagonistas humanos).
--   atitude     : Amigavel | Neutro | Hostil  (filtro principal da galeria)
--   localizacao : região/cidade onde costuma ser encontrado
--   campanha_id : opcional — vínculo a uma campanha
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS npcs;
CREATE TABLE npcs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campanha_id   INT UNSIGNED NULL,
    nome          VARCHAR(120) NOT NULL,
    ocupacao      VARCHAR(120) NOT NULL,
    localizacao   VARCHAR(120) NOT NULL DEFAULT 'Desconhecida',
    atitude       ENUM('Amigavel','Neutro','Hostil') NOT NULL DEFAULT 'Neutro',
    foto_arquivo  VARCHAR(160) NULL,
    historia      TEXT         NOT NULL,
    criado_em     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_npcs_nome        (nome),
    INDEX idx_npcs_atitude     (atitude),
    INDEX idx_npcs_localizacao (localizacao),
    INDEX idx_npcs_campanha    (campanha_id),
    CONSTRAINT fk_npc_campanha FOREIGN KEY (campanha_id)
        REFERENCES campanhas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- criaturas - Bestiário paranormal classificado pelos quatro elementos.
--   elemento    : Sangue (vermelho), Morte (branco), Conhecimento (amarelo), Energia (roxo)
--   vd          : Valor de Desafio (decimal: 0.5, 1, 2, ...)
--   pv          : pontos de vida atuais e máximos
--   habilidades : descrição livre das habilidades especiais
--   campanha_id : opcional — vínculo a uma campanha
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS criaturas;
CREATE TABLE criaturas (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campanha_id   INT UNSIGNED NULL,
    nome          VARCHAR(120) NOT NULL,
    elemento      ENUM('Sangue','Morte','Conhecimento','Energia') NOT NULL,
    foto_arquivo  VARCHAR(160) NULL,
    vd            DECIMAL(4,1) NOT NULL DEFAULT 0.0,
    pv_atual      SMALLINT     NOT NULL,
    pv_maximo     SMALLINT     NOT NULL,
    habilidades   TEXT         NOT NULL,
    criado_em     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_criaturas_elemento (elemento),
    INDEX idx_criaturas_campanha (campanha_id),
    CONSTRAINT chk_criaturas_pv  CHECK (pv_atual >= 0 AND pv_atual <= pv_maximo),
    CONSTRAINT chk_criaturas_vd  CHECK (vd >= 0),
    CONSTRAINT fk_criatura_campanha FOREIGN KEY (campanha_id)
        REFERENCES campanhas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- agentes - Ficha de personagem jogador (PJ) completa.
-- Tabelas-filhas (1:N): agente_pericias, agente_ataques,
-- agente_inventario, agente_rituais. Veja migration_003_vtt.sql
-- para a definição completa dessas tabelas.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS agente_rituais;
DROP TABLE IF EXISTS agente_inventario;
DROP TABLE IF EXISTS agente_ataques;
DROP TABLE IF EXISTS agente_pericias;
DROP TABLE IF EXISTS agentes;
CREATE TABLE agentes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campanha_id     INT UNSIGNED NULL,
    nome            VARCHAR(120) NOT NULL,
    jogador         VARCHAR(120) NULL,
    origem          VARCHAR(60)  NULL,
    classe          ENUM('Combatente','Especialista','Ocultista')
                                  NOT NULL DEFAULT 'Combatente',
    nex             TINYINT UNSIGNED NOT NULL DEFAULT 5,
    foto_arquivo    VARCHAR(160) NULL,
    pv_atual        SMALLINT NOT NULL DEFAULT 8,
    pv_maximo       SMALLINT NOT NULL DEFAULT 8,
    san_atual       SMALLINT NOT NULL DEFAULT 4,
    san_maximo      SMALLINT NOT NULL DEFAULT 4,
    pe_atual        SMALLINT NOT NULL DEFAULT 2,
    pe_maximo       SMALLINT NOT NULL DEFAULT 2,
    forca           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    agilidade       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    intelecto       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    vigor           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    presenca        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    pe_por_turno    TINYINT UNSIGNED NOT NULL DEFAULT 2,
    deslocamento    TINYINT UNSIGNED NOT NULL DEFAULT 9,
    defesa          SMALLINT NOT NULL DEFAULT 10,
    resistencias    TEXT NULL,
    proficiencias   TEXT NULL,
    aparencia       TEXT NULL,
    personalidade   TEXT NULL,
    historico       TEXT NULL,
    objetivos       TEXT NULL,
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

CREATE TABLE agente_pericias (
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

CREATE TABLE agente_ataques (
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

CREATE TABLE agente_inventario (
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

CREATE TABLE agente_rituais (
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

-- ---------------------------------------------------------------------
-- log_rolagens - Histórico de cada rolagem feita no Terminal.
-- Regra Ordem Paranormal (d20): rola N d20, mantém o MAIOR.
--   Se atributo = 0, rola 2 e mantém o MENOR (Desastre).
-- Outros tipos (d4..d100): rola 1 dado simples; brilho intenso só em 1 e 20.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS log_rolagens;
CREATE TABLE log_rolagens (
    id                BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    quem_rolou        VARCHAR(120)     NOT NULL,
    descricao         VARCHAR(160)     NOT NULL,
    quantidade_dados  TINYINT UNSIGNED NOT NULL,
    tipo_dado         ENUM('d4','d6','d8','d10','d12','d20','d100')
                                       NOT NULL DEFAULT 'd20',
    resultados_brutos JSON             NOT NULL,
    resultado_final   SMALLINT UNSIGNED NOT NULL,
    eh_critico        BOOLEAN          NOT NULL DEFAULT FALSE,
    eh_desastre       BOOLEAN          NOT NULL DEFAULT FALSE,
    rolado_em         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_rolado_em (rolado_em),
    INDEX idx_log_quem      (quem_rolou),
    INDEX idx_log_tipo_dado (tipo_dado),
    CONSTRAINT chk_log_resultado CHECK (resultado_final BETWEEN 1 AND 2000),
    CONSTRAINT chk_log_qtd       CHECK (quantidade_dados BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- DADOS DE EXEMPLO (opcional - remova se não desejar)
-- ---------------------------------------------------------------------
INSERT INTO npcs (nome, ocupacao, localizacao, atitude, historia) VALUES
 ('Ipiranga Fagundes','Ocultista veterano','Sede da Ordem - SP','Amigavel',
  'Membro fundador. Conhece rituais perdidos do Outro Lado. Carrega cicatrizes do Incidente de 1998.'),
 ('Doutor Mancini','Patologista forense','Hospital das Clinicas','Neutro',
  'Aliado eventual. Faz autopsias discretas em corpos com marcas paranormais. Cobra caro pelo silencio.'),
 ('Cultista do Verme','Lider de seita','Subsolo do Bairro Cemiterio','Hostil',
  'Profere o nome proibido. Marca seguidores com sigilo de Sangue. Visto pela ultima vez na noite do eclipse.');

INSERT INTO criaturas (nome, elemento, vd, pv_atual, pv_maximo, habilidades) VALUES
 ('Servo de Carne','Sangue',1.0,18,18,
  'Investida Brutal: causa 2d6 de dano de impacto. Regeneracao: 1 PV por turno enquanto houver sangue derramado proximo.'),
 ('Aparicao Murmurante','Morte',2.0,12,12,
  'Toque Frio: drena 1d4 PE da vitima. Intangivel: imune a dano fisico nao-ritual.'),
 ('Bibliotecario sem Olhos','Conhecimento',3.0,28,28,
  'Sussurro Proibido: alvo deve passar em teste de Vontade ou ficar enlouquecido por 1d4 turnos. Conhece todas as linguas mortas.'),
 ('Centelha Errante','Energia',0.5,8,8,
  'Descarga: 1d8 de dano eletrico em area. Velocidade Antinatural: 2 acoes por turno.');
