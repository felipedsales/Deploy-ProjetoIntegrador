<?php

namespace App\Models;

/**
 * Entrevista (RF26 / UC-E06 Agendar Entrevista).
 *
 * Os nomes agendamento, cancelar e finalizar vêm do Diagrama de Classe.
 * Toda operação recebe o id da empresa logada e só alcança entrevistas das
 * vagas dela (RN21).
 */
class Entrevista extends Model
{
    protected $table = 'entrevistas';

    /**
     * Duração considerada para detectar conflito de horário (UC-E06): duas
     * entrevistas conflitam quando começam a menos de 60 minutos uma da outra.
     */
    public const DURACAO_MINUTOS = 60;

    /**
     * UC-E06 — agenda a entrevista de um candidato para uma vaga. Se o candidato
     * já tem entrevista agendada nessa vaga, atualiza data, hora e local
     * (cenário alternativo "Reagendamento").
     *
     * @param array $dados vaga_id, candidato_id, data_hora (Y-m-d\TH:i) e local
     * @return array mapa campo → mensagem; vazio quando a entrevista foi gravada
     */
    public function agendamento($empresaId, $dados)
    {
        $vagaId = (int) ($dados['vaga_id'] ?? 0);
        $candidatoId = (int) ($dados['candidato_id'] ?? 0);
        $local = trim((string) ($dados['local'] ?? ''));

        // Pré-condição do UC-E06: a vaga é da empresa e o candidato se candidatou a ela.
        $sql = "SELECT cv.id FROM candidaturas cv
                JOIN vagas v ON v.id = cv.vaga_id
                WHERE cv.vaga_id = ? AND cv.candidato_id = ? AND v.empresa_id = ?";
        if (!$this->db->fetch($sql, [$vagaId, $candidatoId, $empresaId])) {
            return ['candidatura' => 'Candidatura não encontrada para esta vaga.'];
        }

        $erros = [];

        $dataHora = \DateTime::createFromFormat('!Y-m-d\TH:i', (string) ($dados['data_hora'] ?? ''));
        if (!$dataHora) {
            $erros['data_hora'] = 'Informe a data e a hora da entrevista.';
        } elseif ($dataHora <= new \DateTime()) {
            $erros['data_hora'] = 'A entrevista deve ser marcada para uma data e hora futuras.';
        }

        if ($local === '') {
            $erros['local'] = 'Informe o local da entrevista.';
        } elseif (mb_strlen($local) > 200) {
            $erros['local'] = 'O local deve ter no máximo 200 caracteres.';
        }

        if ($erros) {
            return $erros;
        }

        $dataHoraSql = $dataHora->format('Y-m-d H:i:s');
        $atual = $this->db->fetch(
            "SELECT id FROM {$this->table} WHERE vaga_id = ? AND candidato_id = ? AND status = 'Agendada'",
            [$vagaId, $candidatoId]
        );

        if ($this->temConflito($empresaId, $candidatoId, $dataHoraSql, $atual['id'] ?? 0)) {
            return ['data_hora' => 'Horário em conflito com outra entrevista já agendada. Escolha outro horário.'];
        }

        if ($atual) {
            parent::update($atual['id'], ['data_hora' => $dataHoraSql, 'local' => $local]);
        } else {
            parent::create([
                'vaga_id' => $vagaId,
                'candidato_id' => $candidatoId,
                'data_hora' => $dataHoraSql,
                'local' => $local,
            ]);
        }

        return [];
    }

    /**
     * UC-E06, exceção "Conflito de Horário": o horário está ocupado quando a
     * empresa ou o candidato já têm outra entrevista agendada começando a menos
     * de DURACAO_MINUTOS deste.
     */
    private function temConflito($empresaId, $candidatoId, $dataHoraSql, $ignorarId)
    {
        $sql = "SELECT e.id FROM {$this->table} e
                JOIN vagas v ON v.id = e.vaga_id
                WHERE e.status = 'Agendada'
                  AND e.id <> ?
                  AND (e.candidato_id = ? OR v.empresa_id = ?)
                  AND ABS(TIMESTAMPDIFF(MINUTE, e.data_hora, ?)) < ?
                LIMIT 1";

        return (bool) $this->db->fetch(
            $sql,
            [$ignorarId, $candidatoId, $empresaId, $dataHoraSql, self::DURACAO_MINUTOS]
        );
    }

    /** Cancela uma entrevista agendada de uma vaga da empresa. */
    public function cancelar($empresaId, $entrevistaId)
    {
        return $this->encerrar($empresaId, $entrevistaId, 'Cancelada', '');
    }

    /** Marca como realizada uma entrevista agendada cujo horário já chegou. */
    public function finalizar($empresaId, $entrevistaId)
    {
        return $this->encerrar($empresaId, $entrevistaId, 'Realizada', ' AND data_hora <= NOW()');
    }

    private function encerrar($empresaId, $entrevistaId, $status, $condicao)
    {
        $sql = "UPDATE {$this->table} SET status = ?
                WHERE id = ? AND status = 'Agendada'{$condicao}
                  AND vaga_id IN (SELECT id FROM vagas WHERE empresa_id = ?)";

        return $this->db->query($sql, [$status, $entrevistaId, $empresaId])->rowCount() > 0;
    }

    /**
     * Quando a candidatura deixa de existir (o candidato desistiu ou a empresa o
     * removeu da vaga), a entrevista que estava agendada é cancelada.
     */
    public function cancelarDaCandidatura($vagaId, $candidatoId)
    {
        $sql = "UPDATE {$this->table} SET status = 'Cancelada'
                WHERE vaga_id = ? AND candidato_id = ? AND status = 'Agendada'";
        return $this->db->query($sql, [$vagaId, $candidatoId]);
    }

    /**
     * Entrevista mais recente de cada candidato de uma vaga da empresa,
     * indexada pelo id do candidato.
     */
    public function getPorVaga($vagaId, $empresaId)
    {
        $sql = "SELECT e.* FROM {$this->table} e
                JOIN vagas v ON v.id = e.vaga_id
                WHERE e.vaga_id = ? AND v.empresa_id = ?
                ORDER BY e.id";

        return $this->indexar($this->db->fetchAll($sql, [$vagaId, $empresaId]), 'candidato_id');
    }

    /** Entrevista mais recente do candidato em cada vaga, indexada pelo id da vaga. */
    public function getPorCandidato($candidatoId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE candidato_id = ? ORDER BY id";

        return $this->indexar($this->db->fetchAll($sql, [$candidatoId]), 'vaga_id');
    }

    /** Como a lista vem em ordem de criação, a última de cada chave prevalece. */
    private function indexar($entrevistas, $chave)
    {
        $porChave = [];
        foreach ($entrevistas as $entrevista) {
            $porChave[$entrevista[$chave]] = $entrevista;
        }

        return $porChave;
    }
}
