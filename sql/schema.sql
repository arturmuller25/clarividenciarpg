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
-- npcs - Personagens não-jogáveis (aliados, contatos, civis, antagonistas humanos).
--   atitude     : Amigavel | Neutro | Hostil  (filtro principal da galeria)
--   localizacao : região/cidade onde costuma ser encontrado
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS npcs;
CREATE TABLE npcs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(120) NOT NULL,
    ocupacao      VARCHAR(120) NOT NULL,
    localizacao   VARCHAR(120) NOT NULL DEFAULT 'Desconhecida',
    atitude       ENUM('Amigavel','Neutro','Hostil') NOT NULL DEFAULT 'Neutro',
    historia      TEXT         NOT NULL,
    criado_em     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_npcs_nome        (nome),
    INDEX idx_npcs_atitude     (atitude),
    INDEX idx_npcs_localizacao (localizacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- criaturas - Bestiário paranormal classificado pelos quatro elementos.
--   elemento    : Sangue (vermelho), Morte (branco), Conhecimento (amarelo), Energia (roxo)
--   vd          : Valor de Desafio (decimal: 0.5, 1, 2, ...)
--   pv          : pontos de vida atuais e máximos
--   habilidades : descrição livre das habilidades especiais
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS criaturas;
CREATE TABLE criaturas (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(120) NOT NULL,
    elemento      ENUM('Sangue','Morte','Conhecimento','Energia') NOT NULL,
    vd            DECIMAL(4,1) NOT NULL DEFAULT 0.0,
    pv_atual      SMALLINT     NOT NULL,
    pv_maximo     SMALLINT     NOT NULL,
    habilidades   TEXT         NOT NULL,
    criado_em     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_criaturas_elemento (elemento),
    CONSTRAINT chk_criaturas_pv  CHECK (pv_atual >= 0 AND pv_atual <= pv_maximo),
    CONSTRAINT chk_criaturas_vd  CHECK (vd >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- log_rolagens - Histórico de cada rolagem feita no Terminal.
-- Regra do sistema: rola N d20 e mantém o MAIOR. Se atributo = 0, rola 2 e pega o MENOR (Desastre).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS log_rolagens;
CREATE TABLE log_rolagens (
    id                BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    quem_rolou        VARCHAR(120)     NOT NULL,
    descricao         VARCHAR(160)     NOT NULL,
    quantidade_dados  TINYINT UNSIGNED NOT NULL,
    resultados_brutos JSON             NOT NULL,
    resultado_final   TINYINT UNSIGNED NOT NULL,
    eh_critico        BOOLEAN          NOT NULL DEFAULT FALSE,
    eh_desastre       BOOLEAN          NOT NULL DEFAULT FALSE,
    rolado_em         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_rolado_em (rolado_em),
    INDEX idx_log_quem      (quem_rolou),
    CONSTRAINT chk_log_resultado CHECK (resultado_final BETWEEN 1 AND 20),
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
