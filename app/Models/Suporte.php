<?php

namespace App\Models;

/**
 * Suporte ao usuário (RF17): pedido aberto por formulário, com acompanhamento.
 * A resposta é do administrador e fica em Administrador, junto do log (RN07).
 */
class Suporte extends Model
{
    protected $table = 'tickets_suporte';

    /** RN15: o suporte responde em até 48 horas úteis (dois dias úteis). */
    public const PRAZO_DIAS_UTEIS = 2;

    /**
     * Abre um pedido de suporte.
     *
     * @return array mapa campo → mensagem; vazio quando o pedido foi aberto
     */
    public function abrir($tipoUsuario, $usuarioId, $assunto, $mensagem)
    {
        $assunto = trim((string) $assunto);
        $mensagem = trim((string) $mensagem);
        $erros = [];

        if ($assunto === '' || mb_strlen($assunto) > 150) {
            $erros['assunto'] = 'Informe o assunto, com até 150 caracteres.';
        }
        if ($mensagem === '' || mb_strlen($mensagem) > 5000) {
            $erros['mensagem'] = 'Descreva o que aconteceu, com até 5000 caracteres.';
        }
        if ($erros) {
            return $erros;
        }

        $sql = "INSERT INTO {$this->table} (tipo_usuario, usuario_id, assunto, mensagem) VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$tipoUsuario, $usuarioId, $assunto, $mensagem]);

        return [];
    }

    /** Pedidos do usuário, do mais recente para o mais antigo, com o prazo de resposta. */
    public function listarDoUsuario($tipoUsuario, $usuarioId)
    {
        $sql = "SELECT id, assunto, mensagem, status, resposta, respondido_em, created_at
                FROM {$this->table} WHERE tipo_usuario = ? AND usuario_id = ? ORDER BY id DESC";
        return $this->comPrazo($this->db->fetchAll($sql, [$tipoUsuario, $usuarioId]));
    }

    /** Todos os pedidos (tela do administrador): abertos primeiro, do mais antigo para o mais novo. */
    public function listarTodos()
    {
        $sql = "SELECT t.*, COALESCE(c.nome, e.nome) AS usuario_nome, COALESCE(c.email, e.email) AS usuario_email
                FROM {$this->table} t
                LEFT JOIN candidatos c ON t.tipo_usuario = 'candidato' AND c.id = t.usuario_id
                LEFT JOIN empresas e ON t.tipo_usuario = 'empresa' AND e.id = t.usuario_id
                ORDER BY t.status = 'Respondido', t.id";
        return $this->comPrazo($this->db->fetchAll($sql));
    }

    /** RN15 — acrescenta a cada pedido o prazo de resposta e se ele já venceu. */
    private function comPrazo($tickets)
    {
        foreach ($tickets as &$ticket) {
            $prazo = self::prazoDeResposta($ticket['created_at']);
            $ticket['prazo'] = $prazo->format('Y-m-d H:i:s');
            $ticket['atrasado'] = $ticket['status'] === 'Aberto' && $prazo < new \DateTime();
        }

        return $tickets;
    }

    /** Data limite da resposta: a abertura mais PRAZO_DIAS_UTEIS dias úteis (sábado e domingo não contam). */
    public static function prazoDeResposta($abertura)
    {
        $prazo = new \DateTime($abertura);
        $restantes = self::PRAZO_DIAS_UTEIS;
        while ($restantes > 0) {
            $prazo->modify('+1 day');
            if ((int) $prazo->format('N') < 6) {
                $restantes--;
            }
        }

        return $prazo;
    }
}
