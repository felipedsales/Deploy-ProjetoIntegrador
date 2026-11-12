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