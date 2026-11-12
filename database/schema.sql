-- Script de criação do banco de dados Ferraz Conecta
-- MySQL 8 (InnoDB, utf8mb4)
--
-- Contém APENAS a estrutura (DDL). Os dados de exemplo, opcionais, ficam em
-- database/seed.sql e devem ser importados depois deste arquivo.
--
-- O nome do banco NÃO é fixado aqui (RNF03: vem da variável de ambiente
-- DB_NAME). Crie o banco antes de importar, já com a codificação correta:
--
--   CREATE DATABASE ferraz_conecta
--     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--
-- Os domínios de valor (no PostgreSQL eram CREATE TYPE ... AS ENUM) agora são
-- ENUM(...) declarados inline em cada coluna.

-- Tabela de usuários (candidatos)
CREATE TABLE IF NOT EXISTS candidatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    cpf VARCHAR(14) UNIQUE,
    data_nascimento DATE,
    endereco TEXT,
    curriculo_path VARCHAR(500),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18) UNIQUE,
    telefone VARCHAR(20),
    endereco TEXT,
    descricao TEXT,
    setor VARCHAR(100),
    logo_path VARCHAR(500),
    website VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de vagas
CREATE TABLE IF NOT EXISTS vagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    requisitos TEXT,
    salario DECIMAL(10,2),
    tipo_contrato ENUM('CLT', 'PJ', 'Freelance', 'Estágio') NOT NULL DEFAULT 'CLT',
    modalidade ENUM('Presencial', 'Remoto', 'Híbrido') NOT NULL DEFAULT 'Presencial',
    localizacao VARCHAR(255),
    beneficios TEXT,
    status ENUM('Ativa', 'Inativa', 'Pausada') NOT NULL DEFAULT 'Ativa',
    -- RN14: prazo de candidatura definido pela empresa. Só vagas com prazo
    -- válido são exibidas; a validade mínima de 7 dias é conferida no back-end.
    data_expiracao DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_vagas_empresa (empresa_id),
    KEY idx_vagas_status (status),
    KEY idx_vagas_expiracao (data_expiracao),
    CONSTRAINT fk_vagas_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de candidaturas
CREATE TABLE IF NOT EXISTS candidaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vaga_id INT NOT NULL,
    candidato_id INT NOT NULL,
    status ENUM('Pendente', 'Em análise', 'Aprovada', 'Rejeitada', 'Contratada') NOT NULL DEFAULT 'Pendente',
    data_candidatura TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,
    -- RN04: um candidato não se candidata duas vezes à mesma vaga.
    -- Esta restrição é a implementação física da regra; o back-end também
    -- verifica antes de inserir e responde HTTP 409.
    -- O índice desta UNIQUE (vaga_id à esquerda) também atende a FK fk_candidaturas_vaga.
    UNIQUE KEY uq_candidaturas_vaga_candidato (vaga_id, candidato_id),
    KEY idx_candidaturas_candidato (candidato_id),
    KEY idx_candidaturas_status (status),
    CONSTRAINT fk_candidaturas_vaga
        FOREIGN KEY (vaga_id) REFERENCES vagas (id) ON DELETE CASCADE,
    CONSTRAINT fk_candidaturas_candidato
        FOREIGN KEY (candidato_id) REFERENCES candidatos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de sessões (guarda o token Bearer da API)
CREATE TABLE IF NOT EXISTS sessoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_usuario ENUM('candidato', 'empresa', 'administrador') NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expira_em TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Busca por token já é coberta pelo índice da UNIQUE acima.
    KEY idx_sessoes_expira (expira_em)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de denúncias
CREATE TABLE IF NOT EXISTS denuncias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vaga_id INT,
    candidato_id INT,
    motivo TEXT NOT NULL,
    descricao TEXT,
    status ENUM('Pendente', 'Em análise', 'Resolvida', 'Descartada') NOT NULL DEFAULT 'Pendente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_denuncias_vaga (vaga_id),
    KEY idx_denuncias_candidato (candidato_id),
    CONSTRAINT fk_denuncias_vaga
        FOREIGN KEY (vaga_id) REFERENCES vagas (id) ON DELETE SET NULL,
    CONSTRAINT fk_denuncias_candidato
        FOREIGN KEY (candidato_id) REFERENCES candidatos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de administradores (RF03, RF16, RN09, RN18, RN21)
-- Perfil da equipe da Prefeitura. Fica separado de candidatos e empresas,
-- como as demais contas do sistema.
CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de favoritos (RF19)
CREATE TABLE IF NOT EXISTS favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidato_id INT NOT NULL,
    vaga_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- UC-C08: não é permitido favoritar a mesma vaga mais de uma vez.
    UNIQUE KEY uq_favoritos_candidato_vaga (candidato_id, vaga_id),
    KEY idx_favoritos_vaga (vaga_id),
    CONSTRAINT fk_favoritos_candidato
        FOREIGN KEY (candidato_id) REFERENCES candidatos (id) ON DELETE CASCADE,
    CONSTRAINT fk_favoritos_vaga
        FOREIGN KEY (vaga_id) REFERENCES vagas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de entrevistas (RF26, UC-E06)
-- Segue o MER: a entrevista liga um candidato a uma vaga.
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
    -- Usado na verificação de conflito de horário do candidato (UC-E06).
    KEY idx_entrevistas_candidato_data (candidato_id, data_hora),
    CONSTRAINT fk_entrevistas_vaga
        FOREIGN KEY (vaga_id) REFERENCES vagas (id) ON DELETE CASCADE,
    CONSTRAINT fk_entrevistas_candidato
        FOREIGN KEY (candidato_id) REFERENCES candidatos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de logs de auditoria (RN07)
-- Registra quem fez, o quê e quando, para toda ação administrativa.
-- O log não é apagado quando o administrador é removido.
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
