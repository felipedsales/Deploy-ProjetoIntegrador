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

-- Garante que os acentos dos ENUMs e dos dados sejam lidos como UTF-8, seja qual
-- for a codificação padrão do cliente usado na importação.
SET NAMES utf8mb4;

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
    endereco_campos TEXT,
    -- RN18: conta suspensa pelo administrador não entra no sistema.
    status ENUM('Ativa', 'Suspensa') NOT NULL DEFAULT 'Ativa',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    -- RF02: nome do responsável pela conta da empresa.
    responsavel VARCHAR(255),
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18) UNIQUE,
    telefone VARCHAR(20),
    endereco TEXT,
    endereco_campos TEXT,
    descricao TEXT,
    setor VARCHAR(100),
    website VARCHAR(255),
    -- RN18: conta suspensa pelo administrador não entra no sistema.
    status ENUM('Ativa', 'Suspensa') NOT NULL DEFAULT 'Ativa',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabela de categorias de vagas (RF12, UC13 Gerenciar Categorias de Vagas).
-- Mantida pelo administrador; a empresa escolhe uma ao cadastrar a vaga.
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
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
    -- RF12: área (categoria) e nível de experiência, usados nos filtros da busca.
    categoria_id INT,
    nivel_experiencia ENUM('Sem experiência', 'Júnior', 'Pleno', 'Sênior'),
    beneficios TEXT,
    status ENUM('Ativa', 'Inativa', 'Pausada') NOT NULL DEFAULT 'Ativa',
    -- RN14: prazo de candidatura definido pela empresa. Só vagas com prazo
    -- válido são exibidas; a validade mínima de 7 dias é conferida no back-end.
    data_expiracao DATE NOT NULL,
    -- RN19: quantas vezes a página da vaga foi aberta por quem não é a empresa dona.
    visualizacoes INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_vagas_empresa (empresa_id),
    KEY idx_vagas_status (status),
    KEY idx_vagas_expiracao (data_expiracao),
    KEY idx_vagas_categoria (categoria_id),
    CONSTRAINT fk_vagas_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE CASCADE,
    -- UC13: categoria em uso não pode ser excluída (sem ON DELETE: o banco recusa).
    CONSTRAINT fk_vagas_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
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

-- Tabela de currículos (RF08, RN02) — entidade Currículo do MER.
-- O arquivo fica gravado no próprio banco (coluna conteudo), e não no disco do
-- servidor: assim ele não se perde quando a aplicação é reimplantada e entra
-- no backup do banco. Um currículo por candidato; enviar de novo substitui.
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

-- Tabela de logos das empresas (RF07).
-- Como os currículos, o arquivo fica no banco para não se perder quando a
-- aplicação é reimplantada. Fica em tabela própria, e não em empresas, para
-- que as consultas de empresas não carreguem a imagem. Um logo por empresa.
CREATE TABLE IF NOT EXISTS logos_empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    tamanho INT NOT NULL,
    conteudo MEDIUMBLOB NOT NULL,
    data_envio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_logos_empresa (empresa_id),
    CONSTRAINT fk_logos_empresa
        FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Histórico de alterações das vagas (RF11).
-- Cada edição grava uma linha com os campos alterados, em JSON:
-- {"campo": {"de": valor anterior, "para": valor novo}}.
CREATE TABLE IF NOT EXISTS vagas_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vaga_id INT NOT NULL,
    alteracoes TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_vagas_historico_vaga (vaga_id),
    CONSTRAINT fk_vagas_historico_vaga
        FOREIGN KEY (vaga_id) REFERENCES vagas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Cartas de apresentação (RF09). Opcionais. Com vaga_id nulo é a carta padrão
-- do perfil; com vaga_id preenchido é a carta enviada para aquela vaga. O
-- arquivo fica no banco, como o currículo.
CREATE TABLE IF NOT EXISTS cartas_apresentacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidato_id INT NOT NULL,
    vaga_id INT,
    nome_arquivo VARCHAR(255) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    tamanho INT NOT NULL,
    conteudo MEDIUMBLOB NOT NULL,
    data_envio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cartas_candidato_vaga (candidato_id, vaga_id),
    KEY idx_cartas_vaga (vaga_id),
    CONSTRAINT fk_cartas_candidato
        FOREIGN KEY (candidato_id) REFERENCES candidatos (id) ON DELETE CASCADE,
    CONSTRAINT fk_cartas_vaga
        FOREIGN KEY (vaga_id) REFERENCES vagas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
