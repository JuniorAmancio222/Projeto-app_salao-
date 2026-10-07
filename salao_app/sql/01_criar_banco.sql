
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS salao;
CREATE DATABASE salao
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE salao;


CREATE TABLE pessoa (
    codigo           INT           NOT NULL AUTO_INCREMENT,
    nome             VARCHAR(100)  NOT NULL,
    data_nascimento  DATE          NULL,
    telefone         VARCHAR(20)   NOT NULL,
    email            VARCHAR(100)  NOT NULL,
    login            VARCHAR(50)   NOT NULL,
    senha            VARCHAR(255)  NOT NULL,
    perfil           ENUM('CLIENTE','PROFISSIONAL','ADMINISTRADOR')
                                   NOT NULL,
    PRIMARY KEY (codigo),
    UNIQUE KEY uq_pessoa_email (email),
    UNIQUE KEY uq_pessoa_login (login)
) ENGINE=InnoDB;


CREATE TABLE cliente (
    codigo           INT           NOT NULL,
    historico_pontos INT           NOT NULL DEFAULT 0,
    data_cadastro    DATE          NOT NULL DEFAULT (CURRENT_DATE),
    preferencias     VARCHAR(255)  NULL,
    PRIMARY KEY (codigo),
    CONSTRAINT fk_cliente_pessoa FOREIGN KEY (codigo)
        REFERENCES pessoa (codigo)
        ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE profissional (
    codigo           INT           NOT NULL,
    especialidade    VARCHAR(100)  NULL,
    comissao         DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    ativo            CHAR(1)       NOT NULL DEFAULT 'S',
    PRIMARY KEY (codigo),
    CONSTRAINT fk_profissional_pessoa FOREIGN KEY (codigo)
        REFERENCES pessoa (codigo)
        ON DELETE CASCADE,
    CONSTRAINT ck_profissional_comissao
        CHECK (comissao BETWEEN 0 AND 100),
    CONSTRAINT ck_profissional_ativo
        CHECK (ativo IN ('S','N'))
) ENGINE=InnoDB;


CREATE TABLE servico (
    codigo           INT            NOT NULL AUTO_INCREMENT,
    nome             VARCHAR(100)   NOT NULL,
    preco            DECIMAL(10,2)  NOT NULL,
    duracao_min      INT            NOT NULL,          -- em minutos
    descricao        VARCHAR(255)   NULL,
    PRIMARY KEY (codigo),
    CONSTRAINT ck_servico_preco   CHECK (preco >= 0),
    CONSTRAINT ck_servico_duracao CHECK (duracao_min > 0)
) ENGINE=InnoDB;


CREATE TABLE agendamento (
    numero              INT            NOT NULL AUTO_INCREMENT,
    data_hora           DATETIME       NOT NULL,
    status              VARCHAR(15)    NOT NULL DEFAULT 'AGENDADO',
    duracao             INT            NOT NULL,       -- em minutos
    valor_total         DECIMAL(10,2)  NOT NULL,
    observacao          VARCHAR(255)   NULL,
    cliente_codigo      INT            NOT NULL,
    profissional_codigo INT            NOT NULL,
    servico_codigo      INT            NOT NULL,
    PRIMARY KEY (numero),
    KEY idx_agend_profissional_data (profissional_codigo, data_hora),
    CONSTRAINT fk_agend_cliente FOREIGN KEY (cliente_codigo)
        REFERENCES cliente (codigo),
    CONSTRAINT fk_agend_profissional FOREIGN KEY (profissional_codigo)
        REFERENCES profissional (codigo),
    CONSTRAINT fk_agend_servico FOREIGN KEY (servico_codigo)
        REFERENCES servico (codigo),
    CONSTRAINT ck_agend_status
        CHECK (status IN ('AGENDADO','CANCELADO','CONCLUIDO')),
    CONSTRAINT ck_agend_duracao CHECK (duracao > 0),
    CONSTRAINT ck_agend_valor   CHECK (valor_total >= 0)
) ENGINE=InnoDB;


CREATE TABLE horario_bloqueado (
    codigo              INT           NOT NULL AUTO_INCREMENT,
    data_hora_inicio    DATETIME      NOT NULL,
    data_hora_fim       DATETIME      NOT NULL,
    motivo              VARCHAR(255)  NULL,
    profissional_codigo INT           NOT NULL,
    servico_codigo      INT           NULL,
    PRIMARY KEY (codigo),
    KEY idx_bloqueio_profissional (profissional_codigo, data_hora_inicio),
    CONSTRAINT fk_bloqueio_profissional FOREIGN KEY (profissional_codigo)
        REFERENCES profissional (codigo),
    CONSTRAINT fk_bloqueio_servico FOREIGN KEY (servico_codigo)
        REFERENCES servico (codigo),
    CONSTRAINT ck_bloqueio_periodo CHECK (data_hora_fim > data_hora_inicio)
) ENGINE=InnoDB;


CREATE TABLE historico_atendimento (
    codigo              INT            NOT NULL AUTO_INCREMENT,
    data_atendimento    DATE           NOT NULL,
    valor_pago          DECIMAL(10,2)  NOT NULL,
    servico_realizado   VARCHAR(100)   NOT NULL,
    observacoes         VARCHAR(255)   NULL,
    agendamento_numero  INT            NOT NULL,
    PRIMARY KEY (codigo),
    KEY idx_historico_agendamento (agendamento_numero),
    CONSTRAINT fk_historico_agendamento FOREIGN KEY (agendamento_numero)
        REFERENCES agendamento (numero),
    CONSTRAINT ck_historico_valor CHECK (valor_pago >= 0)
) ENGINE=InnoDB;


CREATE TABLE financeiro (
    lancamento        INT            NOT NULL AUTO_INCREMENT,
    data_lancamento   DATE           NOT NULL,
    descricao         VARCHAR(150)   NOT NULL,
    valor             DECIMAL(10,2)  NOT NULL,
    tipo              ENUM('RECEITA','DESPESA') NOT NULL DEFAULT 'RECEITA',
    historico_codigo  INT            NULL,
    PRIMARY KEY (lancamento),
    KEY idx_financeiro_data (data_lancamento),
    CONSTRAINT fk_financeiro_historico FOREIGN KEY (historico_codigo)
        REFERENCES historico_atendimento (codigo),
    CONSTRAINT ck_financeiro_valor CHECK (valor > 0)
) ENGINE=InnoDB;


CREATE TABLE relatorio (
    codigo        INT          NOT NULL AUTO_INCREMENT,
    tipo          VARCHAR(50)  NOT NULL,
    data_criacao  DATE         NOT NULL DEFAULT (CURRENT_DATE),
    conteudo      TEXT         NULL,
    PRIMARY KEY (codigo)
) ENGINE=InnoDB;
