<?php

namespace App\Models;

class Candidato extends Model
{
    protected $table = 'candidatos';

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
     * RF01 / UC-C01 — valida o cadastro do candidato.
     * RN01: e-mail único; RN16: formato de e-mail, telefone e CPF; senha segura.
     *
     * @return array mapa campo → mensagem; vazio quando está tudo certo
     */
    public function validarCadastro($dados)
    {
        $erros = $this->validarPerfil($dados);

        if (!Validador::email($dados['email'] ?? '')) {
            $erros['email'] = 'Informe um e-mail válido.';
        } elseif ($this->buscarPorEmail(trim($dados['email']))) {
            $erros['email'] = 'Este e-mail já está cadastrado.';
        }

        if (!Validador::senha($dados['senha'] ?? '')) {
            $erros['senha'] = Validador::REGRA_SENHA;
        }

        if (!Validador::cpf($dados['cpf'] ?? '')) {
            $erros['cpf'] = 'CPF inválido.';
        } elseif ($this->db->fetch("SELECT id FROM {$this->table} WHERE cpf = ?", [Validador::formatarCpf($dados['cpf'])])) {
            $erros['cpf'] = 'Este CPF já está cadastrado.';
        }

        $nascimento = \DateTime::createFromFormat('!Y-m-d', (string) ($dados['data_nascimento'] ?? ''));
        if (!$nascimento || $nascimento->format('Y-m-d') !== $dados['data_nascimento'] || $nascimento >= new \DateTime('today')) {
            $erros['data_nascimento'] = 'Informe uma data de nascimento válida.';
        }

        return $erros;
    }

    /**
     * RF06 — valida os dados que o candidato pode alterar no perfil.
     * A nova senha é opcional; quando vem, segue a regra de senha.
     */
    public function validarPerfil($dados)
    {
        $erros = [];

        if (trim((string) ($dados['nome'] ?? '')) === '') {
            $erros['nome'] = 'Informe o nome completo.';
        }
        if (!Validador::telefone($dados['telefone'] ?? '')) {
            $erros['telefone'] = 'Informe um telefone válido, com DDD.';
        }
        if (trim((string) ($dados['endereco'] ?? '')) === '') {
            $erros['endereco'] = 'Informe o endereço.';
        }
        if (($dados['nova_senha'] ?? '') !== '' && !Validador::senha($dados['nova_senha'])) {
            $erros['nova_senha'] = Validador::REGRA_SENHA;
        }

        return $erros;
    }

    /** RF01 — grava o candidato com os dados já validados; CPF sempre no mesmo formato. */
    public function cadastrar($dados)
    {
        return $this->create([
            'nome' => trim($dados['nome']),
            'email' => trim($dados['email']),
            'senha' => $dados['senha'],
            'telefone' => trim($dados['telefone']),
            'cpf' => Validador::formatarCpf($dados['cpf']),
            'data_nascimento' => $dados['data_nascimento'],
            'endereco' => trim($dados['endereco']),
        ]);
    }

    /** RF06 — grava as alterações de perfil já validadas. */
    public function atualizarPerfil($candidatoId, $dados)
    {
        $alteracoes = [
            'nome' => trim($dados['nome']),
            'telefone' => trim($dados['telefone']),
            'endereco' => trim($dados['endereco']),
        ];
        if (($dados['nova_senha'] ?? '') !== '') {
            $alteracoes['senha'] = $dados['nova_senha'];
        }

        return $this->update($candidatoId, $alteracoes);
    }

    /**
     * RF08 / RN02 — recebe o currículo (PDF, DOC ou DOCX) e grava no banco,
     * substituindo o anterior. O candidato envia uma vez e o arquivo fica na
     * conta até ele trocar ou excluir.
     *
     * @return string|null mensagem de erro, ou null quando o currículo foi gravado
     */
    public function salvarCurriculo($candidatoId, $arquivo)
    {
        $validado = Arquivo::validar($arquivo, Arquivo::CURRICULO);
        if (isset($validado['erro'])) {
            return $validado['erro'];
        }

        $conteudo = file_get_contents($validado['temporario']);
        if ($conteudo === false || $conteudo === '') {
            return 'Não foi possível receber o arquivo. Tente novamente.';
        }

        $sql = "INSERT INTO curriculos (candidato_id, nome_arquivo, tipo, tamanho, conteudo)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE nome_arquivo = VALUES(nome_arquivo), tipo = VALUES(tipo),
                    tamanho = VALUES(tamanho), conteudo = VALUES(conteudo), data_envio = CURRENT_TIMESTAMP";
        $this->db->query($sql, [$candidatoId, $validado['nome'], $validado['tipo'], $validado['tamanho'], $conteudo]);

        return null;
    }

    /** RN02 — o candidato pode excluir o currículo a qualquer momento. */
    public function removerCurriculo($candidatoId)
    {
        return $this->db->query("DELETE FROM curriculos WHERE candidato_id = ?", [$candidatoId]);
    }

    /** Dados do currículo para exibição, sem carregar o arquivo. */
    public function getResumoCurriculo($candidatoId)
    {
        $sql = "SELECT nome_arquivo, tamanho, data_envio FROM curriculos WHERE candidato_id = ?";
        return $this->db->fetch($sql, [$candidatoId]);
    }

    /** Currículo completo, com o conteúdo do arquivo, para download. */
    public function getCurriculo($candidatoId)
    {
        $sql = "SELECT nome_arquivo, tipo, conteudo FROM curriculos WHERE candidato_id = ?";
        return $this->db->fetch($sql, [$candidatoId]);
    }

    /** RF13: a candidatura exige currículo anexado ao perfil. */
    public function temCurriculo($candidatoId)
    {
        return (bool) $this->getResumoCurriculo($candidatoId);
    }

    public function buscarPorEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }

    public function buscarPorProvider($provider, $providerId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE provider = ? AND provider_id = ?";
        return $this->db->fetch($sql, [$provider, $providerId]);
    }

    public function buscarPorEmailOuProvider($email, $provider = null, $providerId = null)
    {
        if ($provider && $providerId) {
            $sql = "SELECT * FROM {$this->table} WHERE email = ? OR (provider = ? AND provider_id = ?)";
            return $this->db->fetch($sql, [$email, $provider, $providerId]);
        } else {
            return $this->buscarPorEmail($email);
        }
    }

    public function getMinhasCandidaturas($candidatoId)
    {
        $sql = "SELECT v.*, v.id as vaga_id, e.nome, cv.data_candidatura, cv.status
                FROM candidaturas cv
                JOIN vagas v ON cv.vaga_id = v.id
                JOIN empresas e ON v.empresa_id = e.id
                WHERE cv.candidato_id = ?
                ORDER BY cv.data_candidatura DESC";
        return $this->db->fetchAll($sql, [$candidatoId]);
    }

    public function candidatarVaga($candidatoId, $vagaId)
    {
        // Verifica se já se candidatou (RN04)
        $sql = "SELECT id FROM candidaturas WHERE candidato_id = ? AND vaga_id = ?";
        $existente = $this->db->fetch($sql, [$candidatoId, $vagaId]);

        if ($existente) {
            return false; // Já se candidatou
        }

        try {
            $sql = "INSERT INTO candidaturas (vaga_id, candidato_id, status, data_candidatura) VALUES (?, ?, 'Pendente', NOW())";
            return $this->db->query($sql, [$vagaId, $candidatoId]);
        } catch (\PDOException $e) {
            // RN04: a UNIQUE (vaga_id, candidato_id) é a implementação física da regra.
            // Não confiar só na checagem acima — outra requisição concorrente pode ter
            // inserido a candidatura entre o SELECT e o INSERT.
            $sqlstate = $e->errorInfo[0] ?? $e->getCode();
            if ($sqlstate === '23000') {
                return false;
            }
            throw $e;
        }
    }

    /**
     * RF19 / UC-C08 (Diagrama de Classe: Candidato.favoritarVaga).
     * Salva a vaga na lista de favoritos. Devolve false se já estava favoritada:
     * a UNIQUE (candidato_id, vaga_id) é a implementação física da regra.
     */
    public function favoritarVaga($candidatoId, $vagaId)
    {
        if ($this->vagaFavoritada($candidatoId, $vagaId)) {
            return false;
        }

        try {
            $sql = "INSERT INTO favoritos (candidato_id, vaga_id) VALUES (?, ?)";
            $this->db->query($sql, [$candidatoId, $vagaId]);
            return true;
        } catch (\PDOException $e) {
            // Requisição concorrente favoritou entre o SELECT e o INSERT.
            if (($e->errorInfo[0] ?? $e->getCode()) === '23000') {
                return false;
            }
            throw $e;
        }
    }

    public function desfavoritarVaga($candidatoId, $vagaId)
    {
        $sql = "DELETE FROM favoritos WHERE candidato_id = ? AND vaga_id = ?";
        return $this->db->query($sql, [$candidatoId, $vagaId]);
    }

    public function vagaFavoritada($candidatoId, $vagaId)
    {
        $sql = "SELECT id FROM favoritos WHERE candidato_id = ? AND vaga_id = ?";
        return (bool) $this->db->fetch($sql, [$candidatoId, $vagaId]);
    }

    /**
     * "Minhas vagas favoritas" (UC-C08). A coluna `disponivel` indica se a vaga
     * ainda está ativa e dentro do prazo (RN14); quando não está, a tela avisa
     * que a vaga não está mais disponível.
     */
    public function getVagasFavoritas($candidatoId)
    {
        $sql = "SELECT v.*, e.nome AS empresa_nome, f.created_at AS favoritada_em,
                       (v.status = 'Ativa' AND v.data_expiracao >= CURRENT_DATE) AS disponivel
                FROM favoritos f
                JOIN vagas v ON f.vaga_id = v.id
                JOIN empresas e ON v.empresa_id = e.id
                WHERE f.candidato_id = ?
                ORDER BY f.created_at DESC";
        return $this->db->fetchAll($sql, [$candidatoId]);
    }

    public function desistirCandidatura($candidatoId, $vagaId)
    {
        $sql = "DELETE FROM candidaturas WHERE candidato_id = ? AND vaga_id = ?";
        $desistencia = $this->db->query($sql, [$candidatoId, $vagaId]);

        // Sem candidatura, a entrevista que estava agendada perde o sentido (RF26).
        if ($desistencia->rowCount() > 0) {
            (new Entrevista())->cancelarDaCandidatura($vagaId, $candidatoId);
        }

        return $desistencia;
    }

    public function getEstatisticas()
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['total'];
    }
} 