<?php

namespace App\Models;

/**
 * Códigos de uso único enviados por e-mail (tabela tokens_email): link de
 * confirmação (RN22), link de recuperação de senha (RF05) e código do segundo
 * fator (RNF09). O banco guarda só o hash; o código em si só existe no e-mail.
 */
class TokenEmail extends Model
{
    protected $table = 'tokens_email';

    public const CONFIRMACAO = 'confirmacao';
    public const RECUPERACAO = 'recuperacao';
    public const MULTIFATOR = 'multifator';

    /** Validade de cada finalidade, em minutos. RF05: link de 15 minutos. */
    public const VALIDADE = [
        self::CONFIRMACAO => 1440,
        self::RECUPERACAO => 15,
        self::MULTIFATOR => 10,
    ];

    /** Intervalo mínimo, em segundos, entre dois envios para a mesma conta. */
    public const INTERVALO_REENVIO = 60;

    /** Tentativas erradas aceitas para um código de segundo fator. */
    public const MAX_TENTATIVAS = 5;

    /**
     * Gera um código novo e invalida os anteriores da mesma finalidade.
     *
     * @return string|null o código em texto puro, ou null se outro foi gerado
     *                     há menos de INTERVALO_REENVIO segundos
     */
    public function gerar($tipoUsuario, $usuarioId, $finalidade)
    {
        $recente = $this->db->fetch(
            "SELECT 1 FROM {$this->table}
             WHERE tipo_usuario = ? AND usuario_id = ? AND finalidade = ?
               AND created_at > DATE_SUB(NOW(), INTERVAL " . self::INTERVALO_REENVIO . " SECOND)",
            [$tipoUsuario, $usuarioId, $finalidade]
        );
        if ($recente) {
            return null;
        }

        $this->invalidar($tipoUsuario, $usuarioId, $finalidade);

        $codigo = $finalidade === self::MULTIFATOR
            ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT)
            : bin2hex(random_bytes(32));

        $this->db->query(
            "INSERT INTO {$this->table} (tipo_usuario, usuario_id, finalidade, token_hash, expira_em)
             VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL " . (int) self::VALIDADE[$finalidade] . " MINUTE))",
            [$tipoUsuario, $usuarioId, $finalidade, hash('sha256', $codigo)]
        );

        return $codigo;
    }

    /** Busca, pelo código do link, um token ainda válido da finalidade. */
    public function buscarValido($codigo, $finalidade)
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table}
             WHERE token_hash = ? AND finalidade = ? AND usado_em IS NULL AND expira_em > NOW()",
            [hash('sha256', (string) $codigo), $finalidade]
        );
    }

    /**
     * Confere o código de segundo fator da conta. Cada erro conta uma
     * tentativa; ao atingir MAX_TENTATIVAS o código deixa de valer.
     */
    public function conferirCodigo($tipoUsuario, $usuarioId, $codigo)
    {
        $token = $this->db->fetch(
            "SELECT * FROM {$this->table}
             WHERE tipo_usuario = ? AND usuario_id = ? AND finalidade = ?
               AND usado_em IS NULL AND expira_em > NOW() AND tentativas < ?
             ORDER BY id DESC LIMIT 1",
            [$tipoUsuario, $usuarioId, self::MULTIFATOR, self::MAX_TENTATIVAS]
        );
        if (!$token) {
            return false;
        }

        if (!hash_equals($token['token_hash'], hash('sha256', trim((string) $codigo)))) {
            $this->db->query("UPDATE {$this->table} SET tentativas = tentativas + 1 WHERE id = ?", [$token['id']]);
            return false;
        }

        $this->marcarUsado($token['id']);
        return true;
    }

    public function marcarUsado($id)
    {
        return $this->db->query("UPDATE {$this->table} SET usado_em = NOW() WHERE id = ?", [$id]);
    }

    /** Invalida os códigos em aberto da conta para a finalidade. */
    public function invalidar($tipoUsuario, $usuarioId, $finalidade)
    {
        return $this->db->query(
            "UPDATE {$this->table} SET usado_em = NOW()
             WHERE tipo_usuario = ? AND usuario_id = ? AND finalidade = ? AND usado_em IS NULL",
            [$tipoUsuario, $usuarioId, $finalidade]
        );
    }
}
