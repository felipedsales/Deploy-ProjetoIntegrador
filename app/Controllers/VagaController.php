<?php

namespace App\Controllers;

use App\Models\Vaga;
use App\Models\Candidato;

class VagaController extends Controller
{
    private $vagaModel;
    private $candidatoModel;

    public function __construct()
    {
        $this->vagaModel = new Vaga();
        $this->candidatoModel = new Candidato();
    }

    /**
     * Retorna uma lista de vagas em formato JSON para a API.
     */
    public function apiIndex()
    {
        header('Content-Type: application/json');
        $busca = $this->getQuery('busca');
        
        if ($busca) {
            $vagas = $this->vagaModel->buscarVagas($busca);
        } else {
            $vagas = $this->vagaModel->findAll();
        }

        echo json_encode($vagas);
        return null; // Retorna nulo para que o dispatcher não tente imprimir nada a mais
    }

    public function index()
    {
        $busca = $this->getQuery('busca');
        
        if ($busca) {
            $vagas = $this->vagaModel->buscarVagas($busca);
        } else {
            $vagas = $this->vagaModel->findAll();
        }

        return $this->render('vagas/index', [
            'vagas' => $vagas,
            'busca' => $busca
        ]);
    }

    public function show($id)
    {
        $vaga = $this->vagaModel->findById($id);
        
        if (!$vaga) {
            $this->redirect('/vagas');
        }

        // RN14: vaga inativa ou fora do prazo só é exibida para a empresa dona.
        $ehDona = $this->getSession('user_type') === 'empresa'
            && $vaga['empresa_id'] == $this->getSession('user_id');
        if (!$this->vagaModel->estaAtiva($vaga) && !$ehDona) {
            $this->redirect('/vagas?error=vaga_indisponivel');
        }

        $favoritada = $this->getSession('user_type') === 'candidato'
            && $this->candidatoModel->vagaFavoritada($this->getSession('user_id'), $vaga['id']);

        return $this->render('vagas/show', [
            'vaga' => $vaga,
            'favoritada' => $favoritada
        ]);
    }

    public function candidatar()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
        }

        if (!$this->isPost()) {
            $this->redirect('/vagas');
        }

        $vagaId = $this->getPost('vaga_id');
        $candidatoId = $this->getSession('user_id');

        // RN14: não se recebe candidatura em vaga inativa ou fora do prazo.
        if (!$this->vagaModel->estaAtiva($this->vagaModel->findById($vagaId))) {
            $this->redirect('/vagas?error=vaga_indisponivel');
        }

        if ($this->candidatoModel->candidatarVaga($candidatoId, $vagaId)) {
            $this->redirect('/?status=candidatura_sucesso');
        } else {
            $this->redirect('/vagas?error=ja_candidatado');
        }
    }

    /** RF19 / UC-C08 Favoritar Vagas. */
    public function favoritar()
    {
        $vagaId = $this->exigirCandidatoEVaga();

        // UC-C08: só se favorita vaga que está visível ao candidato (RN14).
        if (!$this->vagaModel->estaAtiva($this->vagaModel->findById($vagaId))) {
            $this->redirect('/vagas?error=vaga_indisponivel');
        }

        if ($this->candidatoModel->favoritarVaga($this->getSession('user_id'), $vagaId)) {
            $this->redirect("/vagas/{$vagaId}?status=favorito_adicionado");
        } else {
            $this->redirect("/vagas/{$vagaId}?error=ja_favoritada");
        }
    }

    public function desfavoritar()
    {
        $vagaId = $this->exigirCandidatoEVaga();

        $this->candidatoModel->desfavoritarVaga($this->getSession('user_id'), $vagaId);

        // Quem removeu pela lista volta para a lista; a vaga pode nem estar mais disponível.
        if ($this->getPost('origem') === 'lista') {
            $this->redirect('/vagas-favoritas?status=favorito_removido');
        }
        $this->redirect("/vagas/{$vagaId}?status=favorito_removido");
    }

    /** Ações de favorito: exige POST de um candidato logado e devolve o id da vaga. */
    private function exigirCandidatoEVaga()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
        }

        if ($this->getSession('user_type') !== 'candidato' || !$this->isPost()) {
            $this->redirect('/vagas');
        }

        return (int) $this->getPost('vaga_id');
    }

    public function desistir()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/minhas-candidaturas');
        }

        if (!$this->isPost()) {
            $this->redirect('/minhas-candidaturas');
        }

        $vagaId = $this->getPost('vaga_id');
        $candidatoId = $this->getSession('user_id');

        $this->candidatoModel->desistirCandidatura($candidatoId, $vagaId);
        $this->redirect('/minhas-candidaturas?status=desistencia_sucesso');
    }

    public function create()
    {
        $this->requireAuth();
        
        // Verifica se é uma empresa
        if ($this->getSession('user_type') !== 'empresa') {
            $this->redirect('/login-empresa');
        }
        
        if ($this->isPost()) {
            // Usar o campo salario_numerico se disponível, senão converter o salario
            $salario = $this->getPost('salario_numerico');
            if (!$salario) {
                $salario = $this->getPost('salario');
                if ($salario) {
                    // Remove pontos de milhares e substitui vírgula por ponto
                    $salario = str_replace('.', '', $salario);
                    $salario = str_replace(',', '.', $salario);
                    $salario = floatval($salario);
                }
            }
            
            $data = [
                'titulo' => $this->getPost('titulo'),
                'descricao' => $this->getPost('descricao'),
                'requisitos' => $this->getPost('requisitos'),
                'salario' => $salario,
                'tipo_contrato' => $this->getPost('tipo_contrato'),
                'modalidade' => $this->getPost('modalidade'),
                'localizacao' => $this->getPost('localizacao'),
                'beneficios' => $this->getPost('beneficios'),
                'status' => 'Ativa',
                'data_expiracao' => $this->getPost('data_expiracao'),
                'empresa_id' => $this->getSession('user_id'),
            ];

            // RN03 e RN14: a regra fica no Model; aqui só se decide o fluxo.
            $erros = $this->vagaModel->validar($data);
            if ($erros) {
                return $this->render('vagas/create', [
                    'erros' => $erros,
                    'vaga' => $data,
                    'dataMinima' => $this->vagaModel->dataExpiracaoMinima(),
                ]);
            }

            $this->vagaModel->create($data);
            $this->redirect('/painel-empresa?status=vaga_criada');
        }

        return $this->render('vagas/create', [
            'dataMinima' => $this->vagaModel->dataExpiracaoMinima(),
        ]);
    }

    public function edit($id)
    {
        $this->requireAuth();
        
        $vaga = $this->vagaModel->findById($id);
        
        if (!$vaga || $vaga['empresa_id'] != $this->getSession('user_id')) {
            $this->redirect('/painel-empresa');
        }

        if ($this->isPost()) {
            // Usar o campo salario_numerico se disponível, senão converter o salario
            $salario = $this->getPost('salario_numerico');
            if (!$salario) {
                $salario = $this->getPost('salario');
                if ($salario) {
                    // Remove pontos de milhares e substitui vírgula por ponto
                    $salario = str_replace('.', '', $salario);
                    $salario = str_replace(',', '.', $salario);
                    $salario = floatval($salario);
                }
            }
            
            $data = [
                'titulo' => $this->getPost('titulo'),
                'descricao' => $this->getPost('descricao'),
                'requisitos' => $this->getPost('requisitos'),
                'salario' => $salario,
                'tipo_contrato' => $this->getPost('tipo_contrato'),
                'modalidade' => $this->getPost('modalidade'),
                'localizacao' => $this->getPost('localizacao'),
                'beneficios' => $this->getPost('beneficios'),
                'status' => $this->getPost('status', $vaga['status']),
                'data_expiracao' => $this->getPost('data_expiracao'),
            ];

            // RN03 e RN14: os 7 dias mínimos contam da publicação da vaga.
            $erros = $this->vagaModel->validar($data, $vaga['created_at']);
            if ($erros) {
                return $this->render('vagas/edit', [
                    'erros' => $erros,
                    'vaga' => array_merge($vaga, $data),
                    'dataMinima' => $this->vagaModel->dataExpiracaoMinima($vaga['created_at']),
                ]);
            }

            $this->vagaModel->update($id, $data);
            $this->redirect('/painel-empresa?status=vaga_atualizada');
        }

        return $this->render('vagas/edit', [
            'vaga' => $vaga,
            'dataMinima' => $this->vagaModel->dataExpiracaoMinima($vaga['created_at']),
        ]);
    }

    public function delete($id)
    {
        $this->requireAuth();
        
        $vaga = $this->vagaModel->findById($id);
        
        if (!$vaga || $vaga['empresa_id'] != $this->getSession('user_id')) {
            $this->redirect('/painel-empresa');
        }

        $this->vagaModel->delete($id);
        $this->redirect('/painel-empresa?status=vaga_excluida');
    }

    public function denunciar()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
        }

        if ($this->getSession('user_type') !== 'candidato') {
            $this->redirect('/vagas');
        }

        if (!$this->isPost()) {
            $this->redirect('/vagas');
        }

        $vagaId = (int) $this->getPost('vaga_id');
        $motivo = $this->getPost('motivo');
        $descricao = $this->getPost('descricao');
        $candidatoId = $this->getSession('user_id');

        // UC-C09: a denúncia só pode ser feita para conteúdo visível ao candidato.
        if (!$this->vagaModel->estaAtiva($this->vagaModel->findById($vagaId))) {
            $this->redirect('/vagas?error=vaga_indisponivel');
        }

        // Verificar se já denunciou esta vaga
        if ($this->vagaModel->verificarDenunciaExistente($candidatoId, $vagaId)) {
            $this->redirect("/vagas/{$vagaId}?error=ja_denunciada");
        }

        // Validar motivo
        $motivosValidos = ['conteudo_inadequado', 'informacoes_falsas', 'discriminacao', 'outro'];
        if (!in_array($motivo, $motivosValidos)) {
            $this->redirect("/vagas/{$vagaId}?error=motivo_invalido");
        }

        if ($this->vagaModel->denunciarVaga($candidatoId, $vagaId, $motivo, $descricao)) {
            $this->redirect("/vagas/{$vagaId}?status=denuncia_sucesso");
        } else {
            $this->redirect("/vagas/{$vagaId}?error=erro_denuncia");
        }
    }
}