<?php

namespace App\Models;

/**
 * Candidatura vista pelas duas partes: mensagens internas (RF24) e feedback e
 * avaliação (RF15). Toda operação parte de buscarParaUsuario(), que só devolve
 * a candidatura a quem participa dela (RN21).
 */
class Candidatura extends Model
{
    protected $table = 'candidaturas';

    /** RF24: a conversa só existe depois de a candidatura ser aceita. */
    private const STATUS_COM_MENSAGENS = ['Aprovada', 'Contratada'];

    /** RF15: a avaliação é pós-processo. */
    private const STATUS_COM_AVALIACAO = ['Aprovada', 'Rejeitada', 'Contratada'];

    /**
     * A candidatura, com os nomes das partes, se o usuário for o candidato dela
     * ou a empresa dona da vaga; senão, false.
     */
    public function buscarParaUsuario($candidaturaId, $perfil, $usuarioId)
    {
        $dono = $perfil === 'empresa' ? 'v.empresa_id' : 'cv.candidato_id';
        $sql = "SELECT cv.id, cv.status, cv.candidato_id, cv.vaga_id, v.titulo AS vaga_titulo, v.empresa_id,
                       c.nome AS candidato_nome, e.nome AS empresa_nome
                FROM candidaturas cv
                JOIN vagas v ON v.id = cv.vaga_id
                JOIN candidatos c ON c.id = cv.candidato_id
                JOIN empresas e ON e.id = v.empresa_id
                WHERE cv.id = ? AND {$dono} = ?";
        return $this->db->fetch($sql, [$candidaturaId, $usuarioId]);
    }

    public function permiteMensagens($candidatura)
    {
        return in_array($candidatura['status'], self::STATUS_COM_MENSAGENS, true);
    }

    public function permiteAvaliacao($candidatura)
    {
        return in_array($candidatura['status'], self::STATUS_COM_AVALIACAO, true);
    }

    public function getMensagens($candidaturaId)
    {
        $sql = "SELECT remetente, texto, created_at FROM mensagens WHERE candidatura_id = ? ORDER BY id";
        return $this->db->fetchAll($sql, [$candidaturaId]);
    }

    /**
     * RF24 — envia uma mensagem na conversa da candidatura e avisa a outra parte.
     *
     * @return string|null mensagem de erro, ou null quando foi enviada
     */
    public function enviarMensagem($candidatura, $perfil, $texto)
    {
        $texto = trim((string) $texto);

        if (!$this->permiteMensagens($candidatura)) {
            return 'As mensagens ficam disponíveis depois que a candidatura é aceita pela empresa.';
        }
        if ($texto === '') {
            return 'Escreva a mensagem.';
        }
        if (mb_strlen($texto) > 2000) {
            return 'A mensagem deve ter no máximo 2000 caracteres.';
        }

        $this->db->query(
            "INSERT INTO mensagens (candidatura_id, remetente, texto) VALUES (?, ?, ?)",
            [$candidatura['id'], $perfil, $texto]
        );

        [$destino, $destinoId, $autor] = $perfil === 'empresa'
            ? ['candidato', $candidatura['candidato_id'], $candidatura['empresa_nome']]
            : ['empresa', $candidatura['empresa_id'], $candidatura['candidato_nome']];
        (new Notificacao())->criar(
            $destino,
            $destinoId,
            'Nova mensagem',
            "{$autor} enviou uma mensagem sobre a vaga \"{$candidatura['vaga_titulo']}\".",
            "/candidaturas/{$candidatura['id']}/conversa"
        );

        return null;
    }

    /** Avaliações da candidatura, indexadas por autor ('candidato' e 'empresa'). */
    public function getAvaliacoes($candidaturaId)
    {
        $porAutor = [];
        $sql = "SELECT autor, nota, comentario, created_at FROM avaliacoes WHERE candidatura_id = ?";
        foreach ($this->db->fetchAll($sql, [$candidaturaId]) as $avaliacao) {
            $porAutor[$avaliacao['autor']] = $avaliacao;
        }

        return $porAutor;
    }

    /**
     * RF15 — grava (ou atualiza) a avaliação do usuário sobre o processo: o
     * candidato avalia a empresa; a empresa dá o retorno ao candidato.
     *
     * @return string|null mensagem de erro, ou null quando foi gravada
     */
    public function avaliar($candidatura, $perfil, $nota, $comentario)
    {
        $nota = (int) $nota;
        $comentario = trim((string) $comentario);

        if (!$this->permiteAvaliacao($candidatura)) {
            return 'A avaliação fica disponível quando o processo seletivo termina.';
        }
        if ($nota < 1 || $nota > 5) {
            return 'Escolha uma nota de 1 a 5.';
        }
        if (mb_strlen($comentario) > 2000) {
            return 'O comentário deve ter no máximo 2000 caracteres.';
        }

        $sql = "INSERT INTO avaliacoes (candidatura_id, autor, nota, comentario) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE nota = VALUES(nota), comentario = VALUES(comentario), created_at = CURRENT_TIMESTAMP";
        $this->db->query($sql, [$candidatura['id'], $perfil, $nota, $comentario !== '' ? $comentario : null]);

        if ($perfil === 'empresa') {
            (new Notificacao())->criar(
                'candidato',
                $candidatura['candidato_id'],
                'Retorno do processo seletivo',
                "{$candidatura['empresa_nome']} registrou um retorno sobre a vaga \"{$candidatura['vaga_titulo']}\".",
                "/candidaturas/{$candidatura['id']}/conversa"
            );
        }

        return null;
    }

    /** Média e quantidade das avaliações feitas por candidatos a uma empresa. */
    public function getReputacaoEmpresa($empresaId)
    {
        $sql = "SELECT ROUND(AVG(a.nota), 1) AS media, COUNT(*) AS total
                FROM avaliacoes a
                JOIN candidaturas cv ON cv.id = a.candidatura_id
                JOIN vagas v ON v.id = cv.vaga_id
                WHERE a.autor = 'candidato' AND v.empresa_id = ?";
        return $this->db->fetch($sql, [$empresaId]);
    }
}
