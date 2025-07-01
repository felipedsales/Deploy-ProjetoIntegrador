-- Script de criação do banco de dados Ferraz Conecta
-- MySQL 8.0+

-- Criar banco de dados se não existir
CREATE DATABASE IF NOT EXISTS ferraz_conecta
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ferraz_conecta;

-- Tabela de usuários (candidatos)
CREATE TABLE IF NOT EXISTS candidatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    cpf VARCHAR(14) UNIQUE,
    data_nascimento DATE,
    endereco TEXT,
    curriculo_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18) UNIQUE,
    telefone VARCHAR(20),
    endereco TEXT,
    descricao TEXT,
    setor VARCHAR(100),
    logo_path VARCHAR(500),
    website VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de vagas
CREATE TABLE IF NOT EXISTS vagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    requisitos TEXT,
    salario DECIMAL(10,2),
    tipo_contrato ENUM('CLT', 'PJ', 'Freelance', 'Estágio') DEFAULT 'CLT',
    modalidade ENUM('Presencial', 'Remoto', 'Híbrido') DEFAULT 'Presencial',
    localizacao VARCHAR(255),
    beneficios TEXT,
    status ENUM('Ativa', 'Inativa', 'Pausada') DEFAULT 'Ativa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

-- Tabela de candidaturas
CREATE TABLE IF NOT EXISTS candidaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vaga_id INT NOT NULL,
    candidato_id INT NOT NULL,
    status ENUM('Pendente', 'Em análise', 'Aprovada', 'Rejeitada', 'Contratada') DEFAULT 'Pendente',
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE CASCADE,
    FOREIGN KEY (candidato_id) REFERENCES candidatos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_candidatura (vaga_id, candidato_id)
);

-- Tabela de sessões
CREATE TABLE IF NOT EXISTS sessoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_usuario ENUM('candidato', 'empresa') NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expira_em TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de denúncias
CREATE TABLE IF NOT EXISTS denuncias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vaga_id INT,
    candidato_id INT,
    motivo TEXT NOT NULL,
    status ENUM('Pendente', 'Em análise', 'Resolvida', 'Descartada') DEFAULT 'Pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE SET NULL,
    FOREIGN KEY (candidato_id) REFERENCES candidatos(id) ON DELETE SET NULL
);

-- Índices para melhor performance
CREATE INDEX idx_vagas_empresa ON vagas(empresa_id);
CREATE INDEX idx_vagas_status ON vagas(status);
CREATE INDEX idx_candidaturas_vaga ON candidaturas(vaga_id);
CREATE INDEX idx_candidaturas_candidato ON candidaturas(candidato_id);
CREATE INDEX idx_candidaturas_status ON candidaturas(status);
CREATE INDEX idx_sessoes_token ON sessoes(token);
CREATE INDEX idx_sessoes_expira ON sessoes(expira_em);

-- Inserir dados de exemplo (opcional)
INSERT INTO empresas (nome, email, senha, cnpj, telefone, descricao, setor) VALUES
('Empresa Exemplo Ltda', 'contato@empresaexemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '12.345.678/0001-90', '(11) 99999-9999', 'Empresa de tecnologia focada em inovação', 'Tecnologia');

INSERT INTO candidatos (nome, email, senha, telefone, cpf) VALUES
('João Silva', 'joao@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(11) 88888-8888', '123.456.789-00');

INSERT INTO vagas (empresa_id, titulo, descricao, requisitos, salario, tipo_contrato, modalidade, localizacao) VALUES
(1, 'Desenvolvedor PHP', 'Desenvolvedor PHP para projeto de sistema de vagas', 'PHP, MySQL, HTML, CSS, JavaScript', 5000.00, 'CLT', 'Remoto', 'São Paulo, SP'); 