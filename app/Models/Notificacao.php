<?php

namespace App\Models;

/**
 * Notificações na plataforma (RF14). Os outros Models chamam criar() quando
 * acontece algo que o usuário precisa saber: mudança de status da candidatura,
 * entrevista, mensagem, resposta do suporte, vaga compatível com o histórico.
 */
class Notificacao extends Model
{
    protected $table = 'notificacoes';

    public function criar($tipoUsuario, $usuarioId, $titulo, $mensagem, $link = null)
    {
        $sql = "INSERT INTO {$this->table} (tipo_usuario, usuario_id, titulo, mensagem, link) VALUES (?, ?, ?, ?, ?)";
        return $this->db->query($sql, [$tipoUsuario, $usuarioId, mb_substr($titulo, 0, 150), mb_substr($mensagem, 0, 500), $link]);
    }

    /** As 100 notificações mais recentes do usuário. */
    public function listar($tipoUsuario, $usuarioId)
    {
        $sql = "SELECT id, titulo, mensagem, link, lida, created_at FROM {$this->table}
                WHERE tipo_usuario = ? AND usuario_id = ?
                ORDER BY id DESC LIMIT 100";
        return $this->db->fetchAll($sql, [$tipoUsuario, $usuarioId]);
    }

    public function contarNaoLidas($tipoUsuario, $usuarioId)
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table} WHERE tipo_usuario = ? AND usuario_id = ? AND lida = 0";
        return (int) $this->db->fetch($sql, [$tipoUsuario, $usuarioId])['total'];
    }

    public function marcarTodasLidas($tipoUsuario, $usuarioId)
    {
        $sql = "UPDATE {$this->table} SET lida = 1 WHERE tipo_usuario = ? AND usuario_id = ? AND lida = 0";
        return $this->db->query($sql, [$tipoUsuario, $usuarioId]);
    }
}
