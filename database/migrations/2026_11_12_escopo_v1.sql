-- Migration: estrutura que faltava para o escopo da v1 — Ferraz Conecta / MySQL 8
--
-- Para bancos JÁ EXISTENTES (ex.: produção no Railway), criados com a versão
-- anterior de database/schema.sql. Banco novo não precisa deste arquivo:
-- basta importar database/schema.sql, que já está atualizado.
--
-- Faça backup antes de aplicar. Aplicar UMA vez; o passo 1 falha se a coluna
-- já existir.
--
-- Atende: RN14, RF03, RF16, RN09, RN18, RN21, RF19, RF21, RF26, RN07.

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

-- 4) Tabelas novas (mesma definição de database/schema.sql).
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
