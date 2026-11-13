<?php

namespace App\Controllers;

use App\Models\Candidato;
use App\Models\Entrevista;

class CandidatoController extends Controller
{
    private $candidatoModel;

    public function __construct()
    {
        $this->candidatoModel = new Candidato();
    }

    public function minhasCandidaturas()
    {
        $this->requirePerfil('candidato');
        
        $candidatoId = $this->getSession('user_id');
        $candidaturas = $this->candidatoModel->getMinhasCandidaturas($candidatoId);
        
        return $this->render('candidato/minhas_candidaturas', [
            'candidaturas' => $candidaturas,
            // RF26: o candidato acompanha aqui a entrevista marcada pela empresa.
            'entrevistas' => (new Entrevista())->getPorCandidato($candidatoId)
        ]);
    }

    /** RF19 / UC-C08 — cenário alternativo "Acessar Lista de Favoritos". */
    public function vagasFavoritas()
    {
        $this->requirePerfil('candidato');

        $vagas = $this->candidatoModel->getVagasFavoritas($this->getSession('user_id'));

        return $this->render('candidato/vagas_favoritas', [
            'vagas' => $vagas,
            'diasAlerta' => Candidato::DIAS_ALERTA_EXPIRACAO
        ]);
    }

    public function perfil()
    {
        $this->requirePerfil('candidato');
        
        return $this->renderPerfil($this->getSession('user_id'));
    }

    private function renderPerfil($candidatoId, $extra = [])
    {
        return $this->render('candidato/perfil', array_merge([
            'candidato' => $this->candidatoModel->findById($candidatoId),
            'curriculo' => $this->candidatoModel->getResumoCurriculo($candidatoId),
            'carta' => $this->candidatoModel->getResumoCarta($candidatoId),
        ], $extra));
    }

    public function atualizarPerfil()
    {
        $this->requirePerfil('candidato');
        
        if (!$this->isPost()) {
            $this->redirect('/perfil');
        }
        
        $candidatoId = $this->getSession('user_id');
        $data = [
            'nome' => $this->getPost('nome'),
            'telefone' => $this->getPost('telefone'),
            'endereco' => $this->getPost('endereco'),
            'nova_senha' => (string) $this->getPost('nova_senha', '')
        ];

        // RF06: a validação (RN16) fica no Model.
        $erros = $this->candidatoModel->validarPerfil($data);
        if ($erros) {
            unset($data['nova_senha']);
            return $this->renderPerfil($candidatoId, [
                'candidato' => array_merge($this->candidatoModel->findById($candidatoId), $data),
                'erros' => $erros
            ]);
        }

        $this->candidatoModel->atualizarPerfil($candidatoId, $data);
        $this->setSession('user_name', trim($data['nome']));
        $this->redirect('/perfil?status=perfil_atualizado');
    }

    /** RF08 / UC-C05 — envio do currículo (substitui o anterior). */
    public function enviarCurriculo()
    {
        $this->requirePerfil('candidato');

        if (!$this->isPost()) {
            $this->redirect('/perfil');
        }

        $candidatoId = $this->getSession('user_id');
        $erro = $this->candidatoModel->salvarCurriculo($candidatoId, $_FILES['curriculo'] ?? null);
        if ($erro !== null) {
            return $this->renderPerfil($candidatoId, ['erroCurriculo' => $erro]);
        }

        $this->redirect('/perfil?status=curriculo_enviado');
    }

    /** RN02 — o candidato exclui o próprio currículo. */
    public function removerCurriculo()
    {
        $this->requirePerfil('candidato');

        if ($this->isPost()) {
            $this->candidatoModel->removerCurriculo($this->getSession('user_id'));
        }

        $this->redirect('/perfil?status=curriculo_removido');
    }

    /** RF09 — carta de apresentação padrão do perfil. */
    public function enviarCarta()
    {
        $this->requirePerfil('candidato');

        if (!$this->isPost()) {
            $this->redirect('/perfil');
        }

        $candidatoId = $this->getSession('user_id');
        $erro = $this->candidatoModel->salvarCarta($candidatoId, $_FILES['carta'] ?? null);
        if ($erro !== null) {
            return $this->renderPerfil($candidatoId, ['erroCarta' => $erro]);
        }

        $this->redirect('/perfil?status=carta_enviada');
    }

    public function removerCarta()
    {
        $this->requirePerfil('candidato');

        if ($this->isPost()) {
            $this->candidatoModel->removerCarta($this->getSession('user_id'));
        }

        $this->redirect('/perfil?status=carta_removida');
    }

    public function baixarCarta()
    {
        $this->requirePerfil('candidato');

        $this->enviarConteudo(
            $this->candidatoModel->getCarta($this->getSession('user_id')),
            'carta_' . $this->getSession('user_name')
        );
    }

    /** O candidato baixa o próprio currículo. */
    public function baixarCurriculo()
    {
        $this->requirePerfil('candidato');

        $candidatoId = $this->getSession('user_id');
        $this->enviarConteudo(
            $this->candidatoModel->getCurriculo($candidatoId),
            'curriculo_' . $this->getSession('user_name')
        );
    }
} 