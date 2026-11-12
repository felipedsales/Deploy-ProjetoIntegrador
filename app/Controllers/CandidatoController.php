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
            'vagas' => $vagas
        ]);
    }

    public function perfil()
    {
        $this->requirePerfil('candidato');
        
        $candidatoId = $this->getSession('user_id');
        $candidato = $this->candidatoModel->findById($candidatoId);
        
        return $this->render('candidato/perfil', [
            'candidato' => $candidato
        ]);
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
            'endereco' => $this->getPost('endereco')
        ];
        
        // Se uma nova senha foi fornecida
        if ($this->getPost('nova_senha')) {
            $data['senha'] = $this->getPost('nova_senha');
        }
        
        $this->candidatoModel->update($candidatoId, $data);
        $this->redirect('/perfil?status=perfil_atualizado');
    }
} 