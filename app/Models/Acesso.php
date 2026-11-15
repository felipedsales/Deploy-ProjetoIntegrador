<?php

namespace App\Models;

/**
 * Regras de acesso que dependem de e-mail:
 *   - RN22 / RN17 — confirmação de e-mail da conta de candidato e de empresa;
 *   - RF05 — recuperação de senha por link válido por 15 minutos;
 *   - RNF09 / RF03 — segundo fator do administrador, por código enviado por e-mail.
 *
 * Sem serviço de e-mail configurado (App\Models\Email) nada disso pode ser
 * exigido, senão ninguém entraria: a conta nasce confirmada, a recuperação
 * fica indisponível e o administrador entra só com a senha.
 */
class Acesso
{
    private const TABELAS = [
        'candidato' => 'candidatos',
        'empresa' => 'empresas',
        'administrador' => 'administradores',
    ];

    private $db;
    private $tokens;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tokens = new TokenEmail();
    }

    // --- RN22: confirmação de e-mail -------------------------------------

    /** RN22: a conta de candidato ou empresa ainda não confirmou o e-mail? */
    public function emailPendente($conta)
    {
        return (int) ($conta['email_confirmado'] ?? 1) === 0;
    }

    /** RN17: o candidato só se candidata com o e-mail verificado. */
    public function emailConfirmado($tipoUsuario, $usuarioId)
    {
        $conta = $this->buscarConta($tipoUsuario, $usuarioId);

        return $conta && !$this->emailPendente($conta);
    }

    /**
     * Chamado logo depois do cadastro (RF01, RF02).
     *
     * @return string 'confirmada' (sem serviço de e-mail: a conta já pode entrar),
     *                'enviado' ou 'falha_envio' (conta criada, aguardando confirmação)
     */
    public function iniciarConfirmacao($tipoUsuario, $usuarioId)
    {
        if (!Email::configurado()) {
            $this->marcarConfirmado($tipoUsuario, $usuarioId);
            return 'confirmada';
        }

        return $this->enviarConfirmacao($tipoUsuario, $this->buscarConta($tipoUsuario, $usuarioId))
            ? 'enviado'
            : 'falha_envio';
    }

    /**
     * Reenvia o link de confirmação. Não revela se o e-mail existe nem se a
     * conta já estava confirmada: quem chama mostra sempre a mesma mensagem.
     */
    public function reenviarConfirmacao($tipoUsuario, $email)
    {
        $conta = $this->buscarContaPorEmail($tipoUsuario, $email);
        if ($conta && $tipoUsuario !== 'administrador' && $this->emailPendente($conta)) {
            $this->enviarConfirmacao($tipoUsuario, $conta);
        }
    }

    /**
     * Confirma o e-mail a partir do código do link.
     *
     * @return string|false o perfil da conta confirmada, ou false se o link não vale
     */
    public function confirmarEmail($codigo)
    {
        $token = $this->tokens->buscarValido($codigo, TokenEmail::CONFIRMACAO);
        if (!$token) {
            return false;
        }

        $this->marcarConfirmado($token['tipo_usuario'], $token['usuario_id']);
        $this->tokens->marcarUsado($token['id']);

        return $token['tipo_usuario'];
    }

    // --- RF05: recuperação de senha --------------------------------------

    public function recuperacaoDisponivel()
    {
        return Email::configurado();
    }

    /**
     * Envia o link de redefinição, válido por 15 minutos. Não revela se o
     * e-mail está cadastrado: quem chama mostra sempre a mesma mensagem.
     */
    public function solicitarRecuperacao($tipoUsuario, $email)
    {
        $conta = $this->buscarContaPorEmail($tipoUsuario, $email);
        if (!$conta) {
            return;
        }

        $codigo = $this->tokens->gerar($tipoUsuario, $conta['id'], TokenEmail::RECUPERACAO);
        if ($codigo === null) {
            return;
        }

        $link = Email::urlBase() . '/redefinir-senha/' . $codigo;
        Email::enviar($conta['email'], $conta['nome'], 'Redefinição de senha — Ferraz Conecta', $this->mensagem(
            $conta['nome'],
            'Recebemos um pedido para redefinir a senha da sua conta no Ferraz Conecta.',
            'Redefinir senha',
            $link,
            'O link vale por 15 minutos e só pode ser usado uma vez. Se você não fez este pedido, ignore esta mensagem: sua senha continua a mesma.'
        ));
    }

    /** O link de redefinição ainda vale? */
    public function linkRecuperacaoValido($codigo)
    {
        return (bool) $this->tokens->buscarValido($codigo, TokenEmail::RECUPERACAO);
    }

    /**
     * Troca a senha a partir do link de recuperação.
     *
     * @return array mapa campo → mensagem; vazio quando a senha foi trocada
     */
    public function redefinirSenha($codigo, $novaSenha, $confirmacao)
    {
        $token = $this->tokens->buscarValido($codigo, TokenEmail::RECUPERACAO);
        if (!$token) {
            return ['link' => 'Este link expirou ou já foi usado. Peça um novo.'];
        }
        if (!Validador::senha($novaSenha)) {
            return ['senha' => Validador::REGRA_SENHA];
        }
        if ($novaSenha !== $confirmacao) {
            return ['confirmacao' => 'As senhas não conferem.'];
        }

        $tabela = self::TABELAS[$token['tipo_usuario']];
        // RNF03: só o hash é gravado.
        $this->db->query(
            "UPDATE {$tabela} SET senha = ? WHERE id = ?",
            [password_hash($novaSenha, PASSWORD_DEFAULT), $token['usuario_id']]
        );
        // Quem abriu o link provou que recebe o e-mail: vale também como confirmação (RN22).
        if ($token['tipo_usuario'] !== 'administrador') {
            $this->marcarConfirmado($token['tipo_usuario'], $token['usuario_id']);
        }
        $this->tokens->marcarUsado($token['id']);

        return [];
    }

    // --- RNF09 / RF03: segundo fator do administrador --------------------

    /** O segundo fator é exigido sempre que há como enviar o código. */
    public function multifatorExigido()
    {
        return Email::configurado();
    }

    /**
     * Gera e envia o código de 6 dígitos, válido por 10 minutos.
     *
     * @return string 'enviado', 'aguarde' (código gerado há menos de um minuto)
     *                ou 'falha_envio'
     */
    public function enviarCodigoMultifator($administradorId)
    {
        $conta = $this->buscarConta('administrador', $administradorId);
        $codigo = $this->tokens->gerar('administrador', $administradorId, TokenEmail::MULTIFATOR);
        if ($codigo === null) {
            return 'aguarde';
        }

        $html = '<p>Olá, ' . htmlspecialchars($conta['nome']) . '.</p>'
            . '<p>Seu código de acesso ao painel administrativo do Ferraz Conecta é:</p>'
            . '<p style="font-size:28px;letter-spacing:6px;font-weight:bold">' . $codigo . '</p>'
            . '<p>O código vale por 10 minutos. Se você não tentou entrar, troque sua senha.</p>';

        if (!Email::enviar($conta['email'], $conta['nome'], 'Código de acesso — Ferraz Conecta', $html)) {
            $this->tokens->invalidar('administrador', $administradorId, TokenEmail::MULTIFATOR);
            return 'falha_envio';
        }

        return 'enviado';
    }

    public function conferirCodigoMultifator($administradorId, $codigo)
    {
        return $this->tokens->conferirCodigo('administrador', $administradorId, $codigo);
    }

    // --- apoio ------------------------------------------------------------

    private function enviarConfirmacao($tipoUsuario, $conta)
    {
        $codigo = $this->tokens->gerar($tipoUsuario, $conta['id'], TokenEmail::CONFIRMACAO);
        if ($codigo === null) {
            return true; // já há um link recém-enviado na caixa de entrada
        }

        $link = Email::urlBase() . '/confirmar-email/' . $codigo;

        return Email::enviar($conta['email'], $conta['nome'], 'Confirme seu e-mail — Ferraz Conecta', $this->mensagem(
            $conta['nome'],
            'Falta um passo para ativar sua conta no Ferraz Conecta: confirmar que este e-mail é seu.',
            'Confirmar e-mail',
            $link,
            'O link vale por 24 horas. Se você não criou esta conta, ignore esta mensagem.'
        ));
    }

    private function marcarConfirmado($tipoUsuario, $usuarioId)
    {
        $tabela = self::TABELAS[$tipoUsuario];
        $this->db->query("UPDATE {$tabela} SET email_confirmado = 1 WHERE id = ?", [$usuarioId]);
    }

    private function buscarConta($tipoUsuario, $usuarioId)
    {
        $tabela = self::TABELAS[$tipoUsuario] ?? null;
        if (!$tabela) {
            return false;
        }
        $confirmado = $tipoUsuario === 'administrador' ? '' : ', email_confirmado';

        return $this->db->fetch("SELECT id, nome, email{$confirmado} FROM {$tabela} WHERE id = ?", [$usuarioId]);
    }

    private function buscarContaPorEmail($tipoUsuario, $email)
    {
        $tabela = self::TABELAS[$tipoUsuario] ?? null;
        if (!$tabela) {
            return false;
        }
        $confirmado = $tipoUsuario === 'administrador' ? '' : ', email_confirmado';

        return $this->db->fetch(
            "SELECT id, nome, email{$confirmado} FROM {$tabela} WHERE email = ?",
            [trim((string) $email)]
        );
    }

    private function mensagem($nome, $texto, $rotulo, $link, $rodape)
    {
        $link = htmlspecialchars($link);

        return '<p>Olá, ' . htmlspecialchars($nome) . '.</p>'
            . '<p>' . $texto . '</p>'
            . '<p><a href="' . $link . '" style="display:inline-block;padding:12px 20px;background:#0d6efd;color:#ffffff;text-decoration:none;border-radius:6px">' . $rotulo . '</a></p>'
            . '<p>Se o botão não funcionar, copie este endereço no navegador:<br>' . $link . '</p>'
            . '<p>' . $rodape . '</p>'
            . '<p>Ferraz Conecta — Prefeitura de Ferraz de Vasconcelos</p>';
    }
}
