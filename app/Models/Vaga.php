<?php

namespace App\Models;

class Vaga extends Model
{
    protected $table = 'vagas';

    /** RN14: validade mínima, em dias, do prazo de candidatura de uma vaga. */
    public const VALIDADE_MINIMA_DIAS = 7;

    /**
     * RN14: condição SQL das vagas que podem ser exibidas ao público —
     * status 'Ativa' e prazo de candidatura ainda válido. Espera o alias "v".
     */
    private const CONDICAO_ATIVA = "v.status = 'Ativa' AND v.data_expiracao >= CURRENT_DATE";

    /**
     * RN14 (Diagrama de Classe: Vaga.estaAtiva): a vaga só pode ser exibida e
     * receber candidaturas se estiver com status 'Ativa' e dentro do prazo.
     */
    public function estaAtiva($vaga)
    {
        if (!$vaga || ($vaga['status'] ?? null) !== 'Ativa' || empty($vaga['data_expiracao'])) {
            return false;
        }

        return $vaga['data_expiracao'] >= date('Y-m-d');
    }

    /**
     * Menor data de expiração aceita (RN14): VALIDADE_MINIMA_DIAS dias depois
     * da publicação. Para vaga nova, a publicação é hoje.
     */
    public function dataExpiracaoMinima($dataPublicacao = null)
    {
        $base = $dataPublicacao ? substr($dataPublicacao, 0, 10) : date('Y-m-d');

        return date('Y-m-d', strtotime($base . ' +' . self::VALIDADE_MINIMA_DIAS . ' days'));
    }

    /**
     * Valida os dados de uma vaga antes de gravar (UC-E05 Manter Vagas).
     *  - RN03: título, descrição, requisitos e localização são obrigatórios.
     *  - RN14: data de expiração obrigatória, com validade mínima de 7 dias
     *    contados da publicação.
     *
     * @param array       $dados          campos enviados pelo formulário
     * @param string|null $dataPublicacao created_at da vaga, ao editar
     * @return array mapa campo → mensagem; vazio quando está tudo certo
     */
    public function validar($dados, $dataPublicacao = null)
    {
        $erros = [];

        $obrigatorios = [
            'titulo' => 'Informe o título da vaga.',
            'descricao' => 'Informe a descrição da vaga.',
            'requisitos' => 'Informe os requisitos da vaga.',
            'localizacao' => 'Informe a localização da vaga.',
        ];
        foreach ($obrigatorios as $campo => $mensagem) {
            if (trim((string) ($dados[$campo] ?? '')) === '') {
                $erros[$campo] = $mensagem;
            }
        }

        $dataExpiracao = trim((string) ($dados['data_expiracao'] ?? ''));
        $data = \DateTime::createFromFormat('!Y-m-d', $dataExpiracao);
        if ($dataExpiracao === '') {
            $erros['data_expiracao'] = 'Informe até quando a vaga recebe candidaturas.';
        } elseif (!$data || $data->format('Y-m-d') !== $dataExpiracao) {
            $erros['data_expiracao'] = 'Data de expiração inválida.';
        } else {
            $minima = $this->dataExpiracaoMinima($dataPublicacao);
            if ($dataExpiracao < $minima) {
                $erros['data_expiracao'] = 'A vaga deve ficar aberta por no mínimo '
                    . self::VALIDADE_MINIMA_DIAS . ' dias: escolha uma data a partir de '
                    . date('d/m/Y', strtotime($minima)) . '.';
            }
        }

        return $erros;
    }

    public function getVagasDestaque($limit = 8)
    {
        // RN14: só vagas ativas e dentro do prazo.
        $sql = "SELECT v.* FROM {$this->table} v
                WHERE " . self::CONDICAO_ATIVA . "
                ORDER BY v.id DESC LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    public function buscarVagas($termo)
    {
        $sql = "SELECT v.*, e.nome as empresa_nome 
                FROM {$this->table} v 
                JOIN empresas e ON v.empresa_id = e.id 
                WHERE (v.titulo LIKE ? OR v.descricao LIKE ?)
                  AND " . self::CONDICAO_ATIVA . "
                ORDER BY v.created_at DESC";
        $termo = "%{$termo}%";
        return $this->db->fetchAll($sql, [$termo, $termo]);
    }

    public function getVagasPorEmpresa($empresaId)
    {
        $sql = "SELECT v.*, e.nome as empresa_nome 
                FROM {$this->table} v 
                JOIN empresas e ON v.empresa_id = e.id 
                WHERE v.empresa_id = ? 
                ORDER BY v.created_at DESC";
        return $this->db->fetchAll($sql, [$empresaId]);
    }

    public function getCandidatosVaga($vagaId)
    {
        $sql = "SELECT c.*, cv.data_candidatura, cv.status 
                FROM candidaturas cv 
                JOIN candidatos c ON cv.candidato_id = c.id 
                WHERE cv.vaga_id = ? 
                ORDER BY cv.data_candidatura DESC";
        return $this->db->fetchAll($sql, [$vagaId]);
    }

    public function getEstatisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total_vagas,
                    COUNT(CASE WHEN status = 'Ativa' THEN 1 END) as vagas_ativas,
                    COUNT(CASE WHEN status = 'Inativa' THEN 1 END) as vagas_fechadas
                FROM {$this->table}";
        return $this->db->fetch($sql);
    }

    /** Lista pública de vagas — RN14: só ativas e dentro do prazo. */
    public function findAll()
    {
        $sql = "SELECT v.*, e.nome as empresa_nome 
                FROM {$this->table} v 
                JOIN empresas e ON v.empresa_id = e.id 
                WHERE " . self::CONDICAO_ATIVA . "
                ORDER BY v.created_at DESC";
        return $this->db->fetchAll($sql);
    }

    // Métodos para denúncias
    public function denunciarVaga($candidatoId, $vagaId, $motivo, $descricao = null)
    {
        $sql = "INSERT INTO denuncias (candidato_id, vaga_id, motivo, descricao) 
                VALUES (?, ?, ?, ?)";
        return $this->db->query($sql, [$candidatoId, $vagaId, $motivo, $descricao]);
    }

    public function verificarDenunciaExistente($candidatoId, $vagaId)
    {
        $sql = "SELECT id FROM denuncias WHERE candidato_id = ? AND vaga_id = ?";
        return $this->db->fetch($sql, [$candidatoId, $vagaId]);
    }

    public function getDenunciasVaga($vagaId)
    {
        $sql = "SELECT dv.*, c.nome as candidato_nome 
                FROM denuncias dv 
                JOIN candidatos c ON dv.candidato_id = c.id 
                WHERE dv.vaga_id = ? 
                ORDER BY dv.created_at DESC";
        return $this->db->fetchAll($sql, [$vagaId]);
    }

    public function getTodasDenuncias()
    {
        $sql = "SELECT dv.*, c.nome as candidato_nome, v.titulo as vaga_titulo, e.nome as empresa_nome
                FROM denuncias dv 
                JOIN candidatos c ON dv.candidato_id = c.id 
                JOIN vagas v ON dv.vaga_id = v.id 
                JOIN empresas e ON v.empresa_id = e.id 
                ORDER BY dv.created_at DESC";
        return $this->db->fetchAll($sql);
    }
}
