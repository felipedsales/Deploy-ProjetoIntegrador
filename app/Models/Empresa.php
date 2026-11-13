<?php

namespace App\Models;

class Empresa extends Model
{
    protected $table = 'empresas';

    public function autenticar($email, $senha)
    {
        return $this->autenticarPorEmail($email, $senha);
    }

    /** RNF03: a senha é gravada sempre como hash. */
    public function create($data)
    {
        return parent::create($this->protegerSenha($data));
    }

    /** RNF03: a senha é gravada sempre como hash. */
    public function update($id, $data)
    {
        return parent::update($id, $this->protegerSenha($data));
    }

    /**
     * RF02 / UC-E01 — valida o cadastro da empresa.
     * RN01: e-mail único; RN16: formato de e-mail, telefone e CNPJ; senha segura.
     *
     * @return array mapa campo → mensagem; vazio quando está tudo certo
     */
    public function validarCadastro($dados)
    {
        $erros = $this->validarDadosComuns($dados);

        if (!Validador::email($dados['email'] ?? '')) {
            $erros['email'] = 'Informe um e-mail válido.';
        } elseif ($this->buscarPorEmail(trim($dados['email']))) {
            $erros['email'] = 'Este e-mail já está cadastrado.';
        }

        if (!Validador::senha($dados['senha'] ?? '')) {
            $erros['senha'] = Validador::REGRA_SENHA;
        }

        if (!Validador::cnpj($dados['cnpj'] ?? '')) {
            $erros['cnpj'] = 'CNPJ inválido.';
        } elseif ($this->db->fetch("SELECT id FROM {$this->table} WHERE cnpj = ?", [Validador::formatarCnpj($dados['cnpj'])])) {
            $erros['cnpj'] = 'Este CNPJ já está cadastrado.';
        }

        return $erros;
    }

    /** RF07 — valida os dados que a empresa pode alterar no perfil. */
    public function validarPerfil($dados)
    {
        $erros = $this->validarDadosComuns($dados);

        $website = trim((string) ($dados['website'] ?? ''));
        if ($website !== '' && (!filter_var($website, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $website))) {
            $erros['website'] = 'Informe o site completo, começando com http:// ou https://.';
        }
        if (mb_strlen(trim((string) ($dados['setor'] ?? ''))) > 100) {
            $erros['setor'] = 'O setor deve ter no máximo 100 caracteres.';
        }
        if (($dados['nova_senha'] ?? '') !== '' && !Validador::senha($dados['nova_senha'])) {
            $erros['nova_senha'] = Validador::REGRA_SENHA;
        }

        return $erros;
    }

    private function validarDadosComuns($dados)
    {
        $erros = [];

        if (trim((string) ($dados['nome'] ?? '')) === '') {
            $erros['nome'] = 'Informe a razão social.';
        }
        if (!Validador::telefone($dados['telefone'] ?? '')) {
            $erros['telefone'] = 'Informe um telefone válido, com DDD.';
        }
        if (trim((string) ($dados['endereco'] ?? '')) === '') {
            $erros['endereco'] = 'Informe o endereço.';
        }

        return $erros;
    }

    /** RF02 — grava a empresa com os dados já validados; CNPJ sempre no mesmo formato. */
    public function cadastrar($dados)
    {
        return $this->create([
            'nome' => trim($dados['nome']),
            'email' => trim($dados['email']),
            'senha' => $dados['senha'],
            'telefone' => trim($dados['telefone']),
            'cnpj' => Validador::formatarCnpj($dados['cnpj']),
            'endereco' => trim($dados['endereco']),
            'descricao' => trim((string) ($dados['descricao'] ?? '')),
        ]);
    }

    /** RF07 — grava as alterações de dados cadastrais e descrição institucional. */
    public function atualizarPerfil($empresaId, $dados)
    {
        $alteracoes = [
            'nome' => trim($dados['nome']),
            'telefone' => trim($dados['telefone']),
            'endereco' => trim($dados['endereco']),
            'descricao' => trim((string) ($dados['descricao'] ?? '')),
            'setor' => trim((string) ($dados['setor'] ?? '')) ?: null,
            'website' => trim((string) ($dados['website'] ?? '')) ?: null,
        ];
        if (($dados['nova_senha'] ?? '') !== '') {
            $alteracoes['senha'] = $dados['nova_senha'];
        }

        return $this->update($empresaId, $alteracoes);
    }

    /**
     * RF07 — recebe o logo da empresa e substitui o anterior.
     *
     * @return string|null mensagem de erro, ou null quando o logo foi gravado
     */
    public function salvarLogo($empresaId, $arquivo)
    {
        $resultado = Arquivo::salvar($arquivo, Arquivo::LOGO, $empresaId);
        if (isset($resultado['erro'])) {
            return $resultado['erro'];
        }

        $anterior = $this->findById($empresaId)['logo_path'] ?? null;
        parent::update($empresaId, ['logo_path' => $resultado['caminho']]);
        Arquivo::remover($anterior);

        return null;
    }

    /**
     * RF13 / RN21 — currículo de um candidato, só para a empresa dona de uma
     * vaga à qual ele se candidatou. Devolve nome do candidato e o arquivo
     * (nome_arquivo, tipo, conteudo), ou false.
     */
    public function getCurriculoDeCandidato($empresaId, $vagaId, $candidatoId)
    {
        $sql = "SELECT c.id, c.nome, cu.nome_arquivo, cu.tipo, cu.conteudo
                FROM candidaturas cv
                JOIN candidatos c ON c.id = cv.candidato_id
                JOIN curriculos cu ON cu.candidato_id = c.id
                JOIN vagas v ON v.id = cv.vaga_id
                WHERE cv.vaga_id = ? AND cv.candidato_id = ? AND v.empresa_id = ?";
        return $this->db->fetch($sql, [$vagaId, $candidatoId, $empresaId]);
    }

    public function buscarPorEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }

    public function buscarEmpresas($busca)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE nome LIKE ? OR descricao LIKE ?
                ORDER BY nome ASC";
        $buscaTerm = "%{$busca}%";
        return $this->db->fetchAll($sql, [$buscaTerm, $buscaTerm]);
    }

    /**
     * Vagas de uma empresa. No painel da própria empresa vêm todas; na página
     * pública ($somenteAtivas) só as ativas e dentro do prazo (RN14).
     */
    public function getVagasEmpresa($empresaId, $somenteAtivas = false)
    {
        $filtro = $somenteAtivas ? " AND status = 'Ativa' AND data_expiracao >= CURRENT_DATE" : '';
        $sql = "SELECT * FROM vagas WHERE empresa_id = ?{$filtro} ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$empresaId]);
    }

    public function getCandidatosPorVaga($vagaId, $empresaId)
    {
        // Colunas explícitas: a senha do candidato não vai para a tela, e o
        // status é o da candidatura (candidatos também tem coluna status).
        $sql = "SELECT c.id, c.nome, c.email, c.telefone, c.cpf, c.data_nascimento, c.endereco,
                       cu.id AS curriculo_id, cv.data_candidatura, cv.status
                FROM candidaturas cv
                JOIN candidatos c ON cv.candidato_id = c.id
                LEFT JOIN curriculos cu ON cu.candidato_id = c.id
                JOIN vagas v ON cv.vaga_id = v.id
                WHERE cv.vaga_id = ? AND v.empresa_id = ?
                ORDER BY cv.data_candidatura DESC";
        return $this->db->fetchAll($sql, [$vagaId, $empresaId]);
    }

    /** RN21: a empresa só altera candidaturas das próprias vagas. */
    public function atualizarStatusCandidatura($candidatoId, $vagaId, $status, $empresaId)
    {
        $sql = "UPDATE candidaturas SET status = ?
                WHERE candidato_id = ? AND vaga_id = ?
                  AND vaga_id IN (SELECT id FROM vagas WHERE empresa_id = ?)";
        return $this->db->query($sql, [$status, $candidatoId, $vagaId, $empresaId]);
    }

    /** RN21: a empresa só remove candidaturas das próprias vagas. */
    public function removerCandidato($candidatoId, $vagaId, $empresaId)
    {
        $sql = "DELETE FROM candidaturas
                WHERE candidato_id = ? AND vaga_id = ?
                  AND vaga_id IN (SELECT id FROM vagas WHERE empresa_id = ?)";
        $removida = $this->db->query($sql, [$candidatoId, $vagaId, $empresaId]);

        // Sem candidatura, a entrevista que estava agendada perde o sentido (RF26).
        if ($removida->rowCount() > 0) {
            (new Entrevista())->cancelarDaCandidatura($vagaId, $candidatoId);
        }

        return $removida;
    }

    public function getEstatisticas()
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['total'];
    }
} 