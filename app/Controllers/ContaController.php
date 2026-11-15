<?php

namespace App\Controllers;

use App\Models\Candidatura;
use App\Models\ExportacaoDados;
use App\Models\Notificacao;
use App\Models\Suporte;

/**
 * Recursos da conta comuns a candidato e empresa: notificações (RF14),
 * mensagens internas (RF24), feedback e avaliação (RF15), suporte (RF17) e
 * exportação dos próprios dados (RF23).
 */
class ContaController extends Controller
{
    /** Exige candidato ou empresa logado e devolve [perfil, id] (RN21). */
    private function exigirUsuario()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
        }

        $perfil = $this->getSession('user_type');
        if (!in_array($perfil, ['candidato', 'empresa'], true)) {
            $this->redirect('/admin');
        }

        return [$perfil, $this->getSession('user_id')];
    }

    /** RF14 — central de notificações. Abrir a tela marca tudo como lido. */
    public function notificacoes()
    {
        [$perfil, $usuarioId] = $this->exigirUsuario();

        $notificacaoModel = new Notificacao();
        $notificacoes = $notificacaoModel->listar($perfil, $usuarioId);
        $notificacaoModel->marcarTodasLidas($perfil, $usuarioId);

        return $this->render('conta/notificacoes', ['notificacoes' => $notificacoes]);
    }

    /** RF24 e RF15 — conversa e avaliação de uma candidatura. */
    public function conversa($id)
    {
        [$perfil, $usuarioId] = $this->exigirUsuario();

        return $this->renderConversa($this->buscarCandidatura($id, $perfil, $usuarioId), $perfil);
    }

    public function enviarMensagem($id)
    {
        [$perfil, $usuarioId] = $this->exigirUsuario();
        $candidatura = $this->buscarCandidatura($id, $perfil, $usuarioId);

        $erro = (new Candidatura())->enviarMensagem($candidatura, $perfil, $this->getPost('texto'));
        if ($erro !== null) {
            return $this->renderConversa($candidatura, $perfil, ['erroMensagem' => $erro, 'texto' => $this->getPost('texto')]);
        }

        $this->redirect("/candidaturas/{$candidatura['id']}/conversa#mensagens");
    }

    public function avaliar($id)
    {
        [$perfil, $usuarioId] = $this->exigirUsuario();
        $candidatura = $this->buscarCandidatura($id, $perfil, $usuarioId);

        $erro = (new Candidatura())->avaliar($candidatura, $perfil, $this->getPost('nota'), $this->getPost('comentario'));
        if ($erro !== null) {
            return $this->renderConversa($candidatura, $perfil, ['erroAvaliacao' => $erro]);
        }

        $this->redirect("/candidaturas/{$candidatura['id']}/conversa?status=avaliacao_salva#avaliacao");
    }

    private function buscarCandidatura($id, $perfil, $usuarioId)
    {
        $candidatura = (new Candidatura())->buscarParaUsuario((int) $id, $perfil, $usuarioId);
        if (!$candidatura) {
            $this->redirect($perfil === 'empresa' ? '/painel-empresa' : '/minhas-candidaturas');
        }

        return $candidatura;
    }

    private function renderConversa($candidatura, $perfil, $extra = [])
    {
        $candidaturaModel = new Candidatura();

        return $this->render('conta/conversa', $extra + [
            'candidatura' => $candidatura,
            'perfil' => $perfil,
            'mensagens' => $candidaturaModel->getMensagens($candidatura['id']),
            'avaliacoes' => $candidaturaModel->getAvaliacoes($candidatura['id']),
            'permiteMensagens' => $candidaturaModel->permiteMensagens($candidatura),
            'permiteAvaliacao' => $candidaturaModel->permiteAvaliacao($candidatura),
        ]);
    }

    /** RF17 / UC-C06 — pedidos de suporte do usuário e formulário de abertura. */
    public function suporte()
    {
        [$perfil, $usuarioId] = $this->exigirUsuario();
        $suporteModel = new Suporte();
        $extra = [];

        if ($this->isPost()) {
            $erros = $suporteModel->abrir($perfil, $usuarioId, $this->getPost('assunto'), $this->getPost('mensagem'));
            if (!$erros) {
                $this->redirect('/suporte?status=aberto');
            }
            $extra = ['erros' => $erros, 'enviado' => ['assunto' => $this->getPost('assunto'), 'mensagem' => $this->getPost('mensagem')]];
        }

        return $this->render('conta/suporte', $extra + [
            'tickets' => $suporteModel->listarDoUsuario($perfil, $usuarioId),
            'prazoDias' => Suporte::PRAZO_DIAS_UTEIS,
        ]);
    }

    /** RF23 — o usuário baixa os próprios dados em JSON (LGPD). */
    public function exportarDados()
    {
        [$perfil, $usuarioId] = $this->exigirUsuario();

        $json = json_encode(
            (new ExportacaoDados())->gerar($perfil, $usuarioId),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="meus-dados-ferraz-conecta-' . date('Y-m-d') . '.json"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }
}
