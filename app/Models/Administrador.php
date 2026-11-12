<?php

namespace App\Models;

/**
 * Administrador — equipe da Prefeitura (RF03, RF16).
 *
 * Os nomes gerenciarUsuarios, analisarDenuncias, inativarUsuarios e
 * inativarVagas vêm do Diagrama de Classe. Toda ação que altera dados grava
 * uma linha em logs_auditoria (RN07), na mesma transação da alteração.
 */
class Administrador extends Model
{
    protected $table = 'administradores';

    /** Perfis de usuário que o administrador pode suspender e a tabela de cada um. */
    private const TABELAS_USUARIO = ['candidato' => 'candidatos', 'empresa' => 'empresas'];

    private const STATUS_DENUNCIA = ['Pendente', 'Em análise', 'Resolvida', 'Descartada'];

    public function autenticar($email, $senha)
    {
        return $this->autenticarPorEmail($email, $senha);
    }

    public function buscarPorEmail($email)
    {
        $sql = "SELECT id, nome, email FROM {$this->table} WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }

    public function listar()
    {
        $sql = "SELECT id, nome, email, created_at FROM {$this->table} ORDER BY nome";
        return $this->db->fetchAll($sql);
    }

    /**
     * RF03 — valida os dados de um novo administrador.
     * RN01: e-mail único; RN16: formato de e-mail; senha com no mínimo 8 caracteres.
     *
     * @return array mapa campo → mensagem; vazio quando está tudo certo
     */
    public function validar($dados)
    {
        $erros = [];

        if (trim((string) ($dados['nome'] ?? '')) === '') {
            $erros['nome'] = 'Informe o nome.';
        }

        $email = trim((string) ($dados['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'Informe um e-mail válido.';
        } elseif ($this->buscarPorEmail($email)) {
            $erros['email'] = 'Já existe um administrador com este e-mail.';
        }

        if (strlen((string) ($dados['senha'] ?? '')) < 8) {
            $erros['senha'] = 'A senha deve ter no mínimo 8 caracteres.';
        }

        return $erros;
    }

    /**
     * RF03 — cadastra um administrador. A senha é gravada como hash (RNF03).
     * $autorId é o administrador que fez o cadastro; null quando o cadastro
     * vem da linha de comando (primeiro administrador).
     */
    public function cadastrar($dados, $autorId = null)
    {
        return $this->emTransacao(function () use ($dados, $autorId) {
            $id = parent::create($this->protegerSenha([
                'nome' => trim($dados['nome']),
                'email' => trim($dados['email']),
                'senha' => $dados['senha'],
            ]));
            $this->registrarLog($autorId, 'cadastrar_administrador', 'administradores', $id, $dados['email']);

            return $id;
        });
    }

    /** Números do painel (RF16). */
    public function getResumo()
    {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM denuncias WHERE status IN ('Pendente', 'Em análise')) AS denuncias_abertas,
                    (SELECT COUNT(*) FROM vagas) AS vagas,
                    (SELECT COUNT(*) FROM vagas WHERE status = 'Ativa' AND data_expiracao >= CURRENT_DATE) AS vagas_ativas,
                    (SELECT COUNT(*) FROM candidatos) AS candidatos,
                    (SELECT COUNT(*) FROM empresas) AS empresas,
                    (SELECT COUNT(*) FROM candidatos WHERE status = 'Suspensa')
                        + (SELECT COUNT(*) FROM empresas WHERE status = 'Suspensa') AS contas_suspensas";
        return $this->db->fetch($sql);
    }

    /**
     * RF16 / UC-C10 Moderar Conteúdo — denúncias recebidas, das abertas para as
     * encerradas. LEFT JOIN: a denúncia continua na lista mesmo que a vaga ou o
     * candidato tenham sido removidos.
     */
    public function analisarDenuncias()
    {
        $sql = "SELECT d.*, c.nome AS candidato_nome, v.titulo AS vaga_titulo,
                       v.status AS vaga_status, e.id AS empresa_id, e.nome AS empresa_nome
                FROM denuncias d
                LEFT JOIN candidatos c ON d.candidato_id = c.id
                LEFT JOIN vagas v ON d.vaga_id = v.id
                LEFT JOIN empresas e ON v.empresa_id = e.id
                ORDER BY FIELD(d.status, 'Pendente', 'Em análise', 'Resolvida', 'Descartada'), d.created_at DESC";
        return $this->db->fetchAll($sql);
    }

    /** UC-C10: registra a decisão sobre a denúncia. */
    public function atualizarStatusDenuncia($adminId, $denunciaId, $status)
    {
        if (!in_array($status, self::STATUS_DENUNCIA, true)) {
            return false;
        }

        return $this->emTransacao(function () use ($adminId, $denunciaId, $status) {
            $stmt = $this->db->query("UPDATE denuncias SET status = ? WHERE id = ?", [$status, $denunciaId]);
            if ($stmt->rowCount() === 0) {
                return false;
            }
            $this->registrarLog($adminId, 'atualizar_status_denuncia', 'denuncias', $denunciaId, $status);

            return true;
        });
    }

    /** RF16 — todas as vagas, de qualquer empresa e em qualquer situação. */
    public function listarVagas()
    {
        $sql = "SELECT v.id, v.titulo, v.status, v.data_expiracao, v.created_at, e.nome AS empresa_nome,
                       (SELECT COUNT(*) FROM denuncias d WHERE d.vaga_id = v.id) AS total_denuncias
                FROM vagas v
                JOIN empresas e ON v.empresa_id = e.id
                ORDER BY v.created_at DESC";
        return $this->db->fetchAll($sql);
    }

    /** RF16 (Diagrama de Classe: inativarVagas) — tira a vaga do ar. */
    public function inativarVagas($adminId, $vagaId)
    {
        return $this->mudarStatus($adminId, 'vagas', $vagaId, 'Inativa', 'inativar_vaga');
    }

    public function reativarVaga($adminId, $vagaId)
    {
        return $this->mudarStatus($adminId, 'vagas', $vagaId, 'Ativa', 'reativar_vaga');
    }

    /** RF16 (Diagrama de Classe: gerenciarUsuarios) — candidatos e empresas cadastrados. */
    public function gerenciarUsuarios()
    {
        $candidatos = $this->db->fetchAll(
            "SELECT id, nome, email, status, created_at FROM candidatos ORDER BY nome"
        );
        $empresas = $this->db->fetchAll(
            "SELECT e.id, e.nome, e.email, e.status, e.created_at,
                    (SELECT COUNT(*) FROM vagas v WHERE v.empresa_id = e.id) AS total_vagas
             FROM empresas e ORDER BY e.nome"
        );

        return ['candidatos' => $candidatos, 'empresas' => $empresas];
    }

    /**
     * RN18 (Diagrama de Classe: inativarUsuarios) — suspende a conta de um
     * candidato ou de uma empresa. Ao suspender uma empresa, as vagas ativas
     * dela saem do ar na mesma operação.
     */
    public function inativarUsuarios($adminId, $tipo, $usuarioId)
    {
        $tabela = self::TABELAS_USUARIO[$tipo] ?? null;
        if ($tabela === null) {
            return false;
        }

        return $this->emTransacao(function () use ($adminId, $tipo, $tabela, $usuarioId) {
            $stmt = $this->db->query(
                "UPDATE {$tabela} SET status = 'Suspensa' WHERE id = ? AND status = 'Ativa'",
                [$usuarioId]
            );
            if ($stmt->rowCount() === 0) {
                return false;
            }

            $detalhes = null;
            if ($tipo === 'empresa') {
                $vagas = $this->db->query(
                    "UPDATE vagas SET status = 'Inativa' WHERE empresa_id = ? AND status <> 'Inativa'",
                    [$usuarioId]
                );
                $detalhes = $vagas->rowCount() . ' vaga(s) inativada(s)';
            }
            $this->registrarLog($adminId, 'suspender_' . $tipo, $tabela, $usuarioId, $detalhes);

            return true;
        });
    }

    /** Reativa a conta. As vagas de uma empresa continuam inativas: quem reativa é a empresa. */
    public function reativarUsuario($adminId, $tipo, $usuarioId)
    {
        $tabela = self::TABELAS_USUARIO[$tipo] ?? null;
        if ($tabela === null) {
            return false;
        }

        return $this->mudarStatus($adminId, $tabela, $usuarioId, 'Ativa', 'reativar_' . $tipo);
    }

    /** Troca o status de um registro e grava o log na mesma transação. */
    private function mudarStatus($adminId, $tabela, $id, $status, $acao)
    {
        return $this->emTransacao(function () use ($adminId, $tabela, $id, $status, $acao) {
            $stmt = $this->db->query(
                "UPDATE {$tabela} SET status = ? WHERE id = ? AND status <> ?",
                [$status, $id, $status]
            );
            if ($stmt->rowCount() === 0) {
                return false;
            }
            $this->registrarLog($adminId, $acao, $tabela, $id);

            return true;
        });
    }

    /** RN07: quem fez, o quê, em qual registro e quando (created_at). */
    private function registrarLog($adminId, $acao, $entidade, $entidadeId, $detalhes = null)
    {
        $sql = "INSERT INTO logs_auditoria (administrador_id, acao, entidade, entidade_id, detalhes)
                VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [$adminId, $acao, $entidade, $entidadeId, $detalhes]);
    }

    /** A alteração e o log (RN07) valem juntos ou não valem. */
    private function emTransacao(callable $operacao)
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();

        try {
            $resultado = $operacao();
            $resultado === false ? $pdo->rollBack() : $pdo->commit();

            return $resultado;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
