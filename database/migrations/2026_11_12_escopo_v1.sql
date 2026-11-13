-- Migration: estrutura que faltava para o escopo da v1 — Ferraz Conecta / MySQL 8
--
-- Para bancos JÁ EXISTENTES (ex.: produção no Railway), criados com a versão
-- anterior de database/schema.sql. Banco novo não precisa deste arquivo:
-- basta importar database/schema.sql, que já está atualizado.
--
-- Faça backup antes de aplicar. Aplicar UMA vez; o passo 1 falha se a coluna
-- já existir.
--
-- Atende: RN14, RF03, RF16, RN09, RN18, RN21, RF19, RF21, RF26, RN07, RF08.

-- Garante que os acentos dos ENUMs e dos dados sejam lidos como UTF-8, seja qual
-- for a codificação padrão do cliente usado na importação.
SET NAMES utf8mb4;

-- 1) RN14 — prazo de candidatura da vaga.
--    As vagas que já existem recebem 30 dias a partir de hoje; depois disso a
--    coluna passa a ser obrigatória.
ALTER TABLE vagas ADD COLUMN data_expiracao DATE NULL AFTER status;
UPDATE vagas SET data_expiracao = DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY) WHERE data_expiracao IS NULL;
ALTER TABLE vagas
    MODIFY COLUMN data_expiracao DATE NOT NULL,
    ADD KEY idx_vagas_expiracao (data_expiracao);

-- 2) Perfil administrador nas sessões.
ALTER TABLE sessoes
    MODIFY COLUMN tipo_usuario ENUM('candidato', 'empresa', 'administrador') NOT NULL;

-- 3) RF21 — descrição da denúncia (atributo que o MER e o Diagrama de Classe
--    já previam e o código já enviava).
ALTER TABLE denuncias ADD COLUMN descricao TEXT NULL AFTER motivo;

-- 4) RN18 — suspensão de contas pelo administrador.
ALTER TABLE candidatos
    ADD COLUMN status ENUM('Ativa', 'Suspensa') NOT NULL DEFAULT 'Ativa' AFTER curriculo_path;
ALTER TABLE empresas
    ADD COLUMN status ENUM('Ativa', 'Suspensa') NOT NULL DEFAULT 'Ativa' AFTER website;

-- 5) Tabelas novas (mesma definição de database/schema.sql).
CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidato_id INT NOT NULL,
    vaga_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favoritos_candidato_vaga (candidato_id, vaga_id),
    KEY idx_favoritos_vaga (vaga_id),
    CONSTRAINT fk_favoritos_candidato
        FOREIGN KEY (candidato_id) REFERENCES candidatos (id) ON DELETE CASCADE,
    CONSTRAINT fk_favoritos_vaga
        FOREIGN KEY (vaga_id) REFERENCES vagas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entrevistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vaga_id INT NOT NULL,
    candidato_id INT NOT NULL,
    data_hora DATETIME NOT NULL,
    local VARCHAR(200) NOT NULL,
    status ENUM('Agendada', 'Cancelada', 'Realizada') NOT NULL DEFAULT 'Agendada',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_entrevistas_vaga (vaga_id),
    KEY idx_entrevistas_candidato_data (candidato_id, data_hora),
    CONSTRAINT fk_entrevistas_vaga
        FOREIGN KEY (vaga_id) REFERENCES vagas (id) ON DELETE CASCADE,
    CONSTRAINT fk_entrevistas_candidato
        FOREIGN KEY (candidato_id) REFERENCES candidatos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    administrador_id INT,
    acao VARCHAR(100) NOT NULL,
    entidade VARCHAR(50) NOT NULL,
    entidade_id INT,
    detalhes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_logs_administrador (administrador_id),
    KEY idx_logs_entidade (entidade, entidade_id),
    CONSTRAINT fk_logs_administrador
        FOREIGN KEY (administrador_id) REFERENCES administradores (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 6) RF08 — currículos gravados no banco (entidade Currículo do MER).
--    A coluna candidatos.curriculo_path sai: ela guardava o caminho de um
--    arquivo no disco do servidor, que é apagado a cada deploy, e nenhuma
--    versão publicada do sistema chegou a preenchê-la.
CREATE TABLE IF NOT EXISTS curriculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidato_id INT NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    tamanho INT NOT NULL,
    conteudo MEDIUMBLOB NOT NULL,
    data_envio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_curriculos_candidato (candidato_id),
    CONSTRAINT fk_curriculos_candidato
        FOREIGN KEY (candidato_id) REFERENCES candidatos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE candidatos DROP COLUMN curriculo_path;
