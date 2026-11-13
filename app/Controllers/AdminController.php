<?php

namespace App\Controllers;

use App\Models\Administrador;
use App\Models\Categoria;

/**
 * Painel administrativo (RF03, RF16) — UC-C10 / UC-E14 Moderar Conteúdo.
 * RN09: só administrador autenticado entra aqui. As regras e o registro em
 * log (RN07) ficam no Model; este controller só decide o fluxo.
 */
class AdminController extends Controller
{
    private $administradorModel;

    public function __construct()
    {
        $this->administradorModel = new Administrador();
    }

    public function login()
    {
        if ($this->getSession('user_type') === 'administrador') {
            $this->redirect('/admin');
        }

        if ($this->isPost()) {
            $admin = $this->administradorModel->autenticar($this->getPost('email'), $this->getPost('senha'));

            if (!$admin) {
                return $this->render('admin/login', ['error' => 'Email ou senha incorretos']);
            }

            session_regenerate_id(true);
            $this->setSession('user_id', $admin['id']);
            $this->setSession('user_type', 'administrador');
            $this->setSession('user_name', $admin['nome']);
            $this->redirect('/admin');
        }

        return $this->render('admin/login');
    }

    public function painel()
    {
        $this->requirePerfil('administrador');

        return $this->render('admin/painel', [
            'resumo' => $this->administradorModel->getResumo()
        ]);
    }

    public function denuncias()
    {
        $this->requirePerfil('administrador');

        return $this->render('admin/denuncias', [
            'denuncias' => $this->administradorModel->analisarDenuncias()
        ]);
    }

    public function atualizarDenuncia()
    {
        $adminId = $this->exigirAcao();

        $ok = $this->administradorModel->atualizarStatusDenuncia(
            $adminId,
            (int) $this->getPost('denuncia_id'),
            $this->getPost('status')
        );
        $this->voltar('/admin/denuncias', $ok);
    }

    public function vagas()
    {
        $this->requirePerfil('administrador');

        return $this->render('admin/vagas', [
            'vagas' => $this->administradorModel->listarVagas()
        ]);
    }

    public function inativarVaga()
    {
        $adminId = $this->exigirAcao();

        $ok = $this->administradorModel->inativarVagas($adminId, (int) $this->getPost('vaga_id'));
        $this->voltar($this->origem('/admin/vagas'), $ok);
    }

    public function reativarVaga()
    {
        $adminId = $this->exigirAcao();

        $ok = $this->administradorModel->reativarVaga($adminId, (int) $this->getPost('vaga_id'));
        $this->voltar('/admin/vagas', $ok);
    }

    public function usuarios()
    {
        $this->requirePerfil('administrador');

        return $this->render('admin/usuarios', $this->administradorModel->gerenciarUsuarios());
    }

    public function suspenderUsuario()
    {
        $adminId = $this->exigirAcao();

        $ok = $this->administradorModel->inativarUsuarios(
            $adminId,
            $this->getPost('tipo'),
            (int) $this->getPost('usuario_id')
        );
        $this->voltar('/admin/usuarios', $ok);
    }

    public function reativarUsuario()
    {
        $adminId = $this->exigirAcao();

        $ok = $this->administradorModel->reativarUsuario(
            $adminId,
            $this->getPost('tipo'),
            (int) $this->getPost('usuario_id')
        );
        $this->voltar('/admin/usuarios', $ok);
    }

    /** UC13 Gerenciar Categorias de Vagas. */
    public function categorias()
    {
        $this->requirePerfil('administrador');

        return $this->render('admin/categorias', [
            'categorias' => (new Categoria())->listarComUso()
        ]);
    }

    public function salvarCategorias()
    {
        $adminId = $this->exigirAcao();

        $nomes = $_POST['nome'] ?? [];
        $erros = $this->administradorModel->salvarCategorias(
            $adminId,
            is_array($nomes) ? $nomes : [],
            (string) $this->getPost('nova', '')
        );
        if ($erros) {
            return $this->render('admin/categorias', [
                'categorias' => (new Categoria())->listarComUso(),
                'erros' => $erros,
                'enviado' => ['nome' => is_array($nomes) ? $nomes : [], 'nova' => (string) $this->getPost('nova', '')],
            ]);
        }

        $this->redirect('/admin/categorias?status=ok');
    }

    public function excluirCategoria()
    {
        $adminId = $this->exigirAcao();

        $erro = $this->administradorModel->excluirCategoria($adminId, (int) $this->getPost('categoria_id'));
        if ($erro !== null) {
            return $this->render('admin/categorias', [
                'categorias' => (new Categoria())->listarComUso(),
                'erros' => [$erro],
            ]);
        }

        $this->redirect('/admin/categorias?status=ok');
    }

    public function administradores()
    {
        $this->requirePerfil('administrador');

        return $this->render('admin/administradores', [
            'administradores' => $this->administradorModel->listar()
        ]);
    }

    /** RF03 — cadastro de administrador, feito por outro administrador. */
    public function cadastrarAdministrador()
    {
        $adminId = $this->exigirAcao();

        $dados = [
            'nome' => $this->getPost('nome'),
            'email' => $this->getPost('email'),
            'senha' => $this->getPost('senha'),
        ];

        $erros = $this->administradorModel->validar($dados);
        if ($erros) {
            unset($dados['senha']);
            return $this->render('admin/administradores', [
                'administradores' => $this->administradorModel->listar(),
                'erros' => $erros,
                'dados' => $dados,
            ]);
        }

        $this->administradorModel->cadastrar($dados, $adminId);
        $this->redirect('/admin/administradores?status=ok');
    }

    /** Toda ação do painel é um POST de administrador logado. Devolve o id dele. */
    private function exigirAcao()
    {
        $this->requirePerfil('administrador');

        if (!$this->isPost()) {
            $this->redirect('/admin');
        }

        return $this->getSession('user_id');
    }

    /** Tela para onde voltar depois da ação; só aceita telas do próprio painel. */
    private function origem($padrao)
    {
        return $this->getPost('origem') === 'denuncias' ? '/admin/denuncias' : $padrao;
    }

    private function voltar($url, $ok)
    {
        $this->redirect($url . ($ok ? '?status=ok' : '?error=nao_alterado'));
    }
}
