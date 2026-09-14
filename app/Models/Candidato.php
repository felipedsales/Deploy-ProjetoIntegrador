<?php

namespace App\Models;

class Candidato extends Model
{
    protected $table = 'candidatos';

    public function autenticar($email, $senha)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? AND senha = ?";
        return $this->db->fetch($sql, [$email, $senha]);
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

    public function desistirCandidatura($candidatoId, $vagaId)
    {
        $sql = "DELETE FROM candidaturas WHERE candidato_id = ? AND vaga_id = ?";
        return $this->db->query($sql, [$candidatoId, $vagaId]);
    }

    public function getEstatisticas()
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['total'];
    }
} 