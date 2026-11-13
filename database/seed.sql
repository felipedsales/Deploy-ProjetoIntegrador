-- Dados de exemplo (opcional) — Ferraz Conecta / MySQL 8
--
-- Pré-requisito: importar database/schema.sql antes deste arquivo.
-- Os INSERTs abaixo assumem um banco recém-criado, com os IDs começando em 1
-- (empresa_id = 1 na vaga de exemplo).
--
-- O hash em `senha` é o mesmo para todos os usuários e corresponde a uma senha
-- de teste — NÃO usar em produção.

-- Garante que os acentos dos ENUMs e dos dados sejam lidos como UTF-8, seja qual
-- for a codificação padrão do cliente usado na importação.
SET NAMES utf8mb4;

INSERT INTO empresas (nome, email, senha, cnpj, telefone, descricao, setor) VALUES
('Empresa Exemplo Ltda', 'contato@empresaexemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '11.222.333/0001-81', '(11) 99999-9999', 'Empresa de tecnologia focada em inovação', 'Tecnologia');

INSERT INTO candidatos (nome, email, senha, telefone, cpf) VALUES
('João Silva', 'joao@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '(11) 88888-8888', '529.982.247-25');

INSERT INTO vagas (empresa_id, titulo, descricao, requisitos, salario, tipo_contrato, modalidade, localizacao, data_expiracao) VALUES
(1, 'Desenvolvedor PHP', 'Desenvolvedor PHP para projeto de sistema de vagas', 'PHP, MySQL, HTML, CSS, JavaScript', 5000.00, 'CLT', 'Remoto', 'São Paulo, SP', DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY));

INSERT INTO administradores (nome, email, senha) VALUES
('Administrador Exemplo', 'admin@ferrazconecta.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
