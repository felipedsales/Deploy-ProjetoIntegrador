<?php

namespace App\Controllers;

use App\Models\Acesso;
use App\Models\Candidato;
use App\Models\Empresa;
use App\Models\SocialAuth;

class AuthController extends Controller
{
    private $candidatoModel;
    private $empresaModel;
    private $socialAuth;
    private $acesso;

    public function __construct()
    {
        $this->candidatoModel = new Candidato();
        $this->empresaModel = new Empresa();
        $this->socialAuth = new SocialAuth();
        $this->acesso = new Acesso();
    }

    public function login()
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/');
        }

        if ($this->isPost()) {
            $email = $this->getPost('email');
            $senha = $this->getPost('senha');
            // RN21: esta tela só autentica candidato ou empresa; o perfil gravado na
            // sessão nunca vem direto do formulário.
            $tipo = $this->getPost('tipo') === 'empresa' ? 'empresa' : 'candidato';

            if ($tipo === 'empresa') {
                $user = $this->empresaModel->autenticar($email, $senha);
            } else {
                $user = $this->candidatoModel->autenticar($email, $senha);
            }

            if ($user && $this->candidatoModel->contaSuspensa($user)) {
                return $this->render('auth/login', [
                    'error' => 'Sua conta está suspensa. Entre em contato com a Prefeitura.'
                ]);
            }

            // RN22: a conta só entra depois de confirmar o e-mail.
            if ($user && $this->acesso->emailPendente($user)) {
                return $this->render('auth/confirmacao_pendente', ['email' => $user['email'], 'tipo' => $tipo]);
            }

            if ($user) {
                $this->setSession('user_id', $user['id']);
                $this->setSession('user_type', $tipo);
                $this->setSession('user_name', $user['nome']);
                
                if ($tipo === 'empresa') {
                    $this->redirect('/painel-empresa');
                } else {
                    $this->redirect('/');
                }
            } else {
                return $this->render('auth/login', [
                    'error' => 'Email ou senha incorretos'
                ]);
            }
        }

        return $this->render('auth/login');
    }

    public function loginEmpresa()
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/painel-empresa');
        }

        if ($this->isPost()) {
            $email = $this->getPost('email');
            $senha = $this->getPost('senha');

            $user = $this->empresaModel->autenticar($email, $senha);

            if ($user && $this->empresaModel->contaSuspensa($user)) {
                return $this->render('auth/login_empresa', [
                    'error' => 'Sua conta está suspensa. Entre em contato com a Prefeitura.'
                ]);
            }

            // RN22: a conta só entra depois de confirmar o e-mail.
            if ($user && $this->acesso->emailPendente($user)) {
                return $this->render('auth/confirmacao_pendente', ['email' => $user['email'], 'tipo' => 'empresa']);
            }

            if ($user) {
                $this->setSession('user_id', $user['id']);
                $this->setSession('user_type', 'empresa');
                $this->setSession('user_name', $user['nome']);
                $this->redirect('/painel-empresa');
            } else {
                return $this->render('auth/login_empresa', [
                    'error' => 'Email ou senha incorretos'
                ]);
            }
        }

        return $this->render('auth/login_empresa');
    }

    public function register()
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/');
        }

        if ($this->isPost()) {
            $data = [
                'nome' => $this->getPost('nome'),
                'email' => $this->getPost('email'),
                'senha' => $this->getPost('senha'),
                'telefone' => $this->getPost('telefone'),
                'cpf' => $this->getPost('cpf'),
                'data_nascimento' => $this->getPost('data_nascimento'),
            ];

            // RF01: validação no back-end (RN01, RN16) antes de gravar.
            $erros = $this->candidatoModel->validarCadastro($data);
            if ($erros) {
                unset($data['senha']);
                return $this->render('auth/register', [
                    'erros' => $erros,
                    'dados' => $data
                ]);
            }

            $userId = $this->candidatoModel->cadastrar($data);
            
            // RN22: com serviço de e-mail, a conta só é ativada pelo link enviado.
            if ($userId && ($envio = $this->acesso->iniciarConfirmacao('candidato', $userId)) !== 'confirmada') {
                return $this->render('auth/confirmacao_pendente', [
                    'email' => $data['email'], 'tipo' => 'candidato', 'recemCriada' => true, 'envio' => $envio
                ]);
            }

            if ($userId) {
                $this->setSession('user_id', $userId);
                $this->setSession('user_type', 'candidato');
                $this->setSession('user_name', $data['nome']);
                $this->redirect('/?status=cadastro_sucesso');
            } else {
                return $this->render('auth/register', [
                    'error' => 'Erro ao criar conta'
                ]);
            }
        }

        return $this->render('auth/register');
    }

    public function registerEmpresa()
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/painel-empresa');
        }

        if ($this->isPost()) {
            $data = [
                'nome' => $this->getPost('razao_social'),
                'responsavel' => $this->getPost('responsavel'),
                'email' => $this->getPost('email'),
                'senha' => $this->getPost('senha'),
                'telefone' => $this->getPost('telefone'),
                'cnpj' => $this->getPost('cnpj'),
                'descricao' => $this->getPost('descricao')
            ];

            // RF02: validação no back-end (RN01, RN16) antes de gravar.
            $erros = $this->empresaModel->validarCadastro($data);
            if ($erros) {
                unset($data['senha']);
                return $this->render('auth/register_empresa', [
                    'erros' => $erros,
                    'dados' => $data + ['razao_social' => $data['nome']]
                ]);
            }

            $userId = $this->empresaModel->cadastrar($data);
            
            // RN22: com serviço de e-mail, a conta só é ativada pelo link enviado.
            if ($userId && ($envio = $this->acesso->iniciarConfirmacao('empresa', $userId)) !== 'confirmada') {
                return $this->render('auth/confirmacao_pendente', [
                    'email' => $data['email'], 'tipo' => 'empresa', 'recemCriada' => true, 'envio' => $envio
                ]);
            }

            if ($userId) {
                $this->setSession('user_id', $userId);
                $this->setSession('user_type', 'empresa');
                $this->setSession('user_name', $data['nome']);
                $this->redirect('/painel-empresa?status=cadastro_sucesso');
            } else {
                return $this->render('auth/register_empresa', [
                    'error' => 'Erro ao criar conta'
                ]);
            }
        }

        return $this->render('auth/register_empresa');
    }

    /** RN22 — link de confirmação recebido por e-mail. */
    public function confirmarEmail($token)
    {
        $perfil = $this->acesso->confirmarEmail($token);
        if (!$perfil) {
            return $this->render('auth/confirmacao_pendente', ['linkInvalido' => true]);
        }

        $this->redirect(($perfil === 'empresa' ? '/login-empresa' : '/login') . '?status=email_confirmado');
    }

    /** RN22 — reenvio do link de confirmação. A resposta é a mesma exista ou não a conta. */
    public function reenviarConfirmacao()
    {
        $tipo = $this->getPost('tipo') === 'empresa' ? 'empresa' : 'candidato';
        $email = trim((string) $this->getPost('email'));
        $this->acesso->reenviarConfirmacao($tipo, $email);

        return $this->render('auth/confirmacao_pendente', ['email' => $email, 'tipo' => $tipo, 'reenviado' => true]);
    }

    /** RF05 — pedido do link de redefinição de senha. */
    public function recuperarSenha()
    {
        $dados = ['disponivel' => $this->acesso->recuperacaoDisponivel()];

        if ($this->isPost() && $dados['disponivel']) {
            $tipo = in_array($this->getPost('tipo'), ['candidato', 'empresa', 'administrador'], true)
                ? $this->getPost('tipo')
                : 'candidato';
            $this->acesso->solicitarRecuperacao($tipo, $this->getPost('email'));
            $dados['enviado'] = true;
        }

        return $this->render('auth/recuperar_senha', $dados);
    }

    /** RF05 — troca da senha a partir do link, válido por 15 minutos. */
    public function redefinirSenha($token)
    {
        if (!$this->acesso->linkRecuperacaoValido($token)) {
            return $this->render('auth/redefinir_senha', ['linkInvalido' => true]);
        }

        if ($this->isPost()) {
            $erros = $this->acesso->redefinirSenha($token, $this->getPost('senha'), $this->getPost('confirmacao'));
            if (!$erros) {
                $this->redirect('/login?status=senha_redefinida');
            }

            return $this->render('auth/redefinir_senha', ['token' => $token, 'erros' => $erros]);
        }

        return $this->render('auth/redefinir_senha', ['token' => $token]);
    }

    public function logout()
    {
        $this->destroySession();
        $this->redirect('/');
    }

    public function googleAuth()
    {
        $authUrl = $this->socialAuth->getGoogleAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    public function linkedinAuth()
    {
        $authUrl = $this->socialAuth->getLinkedInAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    public function googleCallback()
    {
        $code = $_GET['code'] ?? null;
        
        if (!$code) {
            $this->redirect('/login?error=google_auth_failed');
        }

        $result = $this->socialAuth->handleGoogleCallback($code);
        
        if ($result) {
            $this->setSession('user_id', $result['user']['id']);
            $this->setSession('user_type', 'candidato');
            $this->setSession('user_name', $result['user']['nome']);
            
            if ($result['action'] === 'register') {
                $this->redirect('/?status=social_register_success');
            } else {
                $this->redirect('/?status=social_login_success');
            }
        } else {
            $this->redirect('/login?error=google_auth_failed');
        }
    }

    public function linkedinCallback()
    {
        $code = $_GET['code'] ?? null;
        
        if (!$code) {
            $this->redirect('/login?error=linkedin_auth_failed');
        }

        $result = $this->socialAuth->handleLinkedInCallback($code);
        
        if ($result) {
            $this->setSession('user_id', $result['user']['id']);
            $this->setSession('user_type', 'candidato');
            $this->setSession('user_name', $result['user']['nome']);
            
            if ($result['action'] === 'register') {
                $this->redirect('/?status=social_register_success');
            } else {
                $this->redirect('/?status=social_login_success');
            }
        } else {
            $this->redirect('/login?error=linkedin_auth_failed');
        }
    }
} 