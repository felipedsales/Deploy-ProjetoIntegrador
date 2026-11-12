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
        $sql = "SELECT c.*, cv.data_candidatura, cv.status
                FROM candidaturas cv
                JOIN candidatos c ON cv.candidato_id = c.id
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
        return $this->db->query($sql, [$candidatoId, $vagaId, $empresaId]);
    }

    public function getEstatisticas()
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['total'];
    }
} 