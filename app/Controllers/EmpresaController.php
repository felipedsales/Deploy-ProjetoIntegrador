<?php

namespace App\Controllers;

use App\Models\Endereco;

use App\Models\Empresa;
use App\Models\Entrevista;
use App\Models\Vaga;

class EmpresaController extends Controller
{
    private $empresaModel;
    private $vagaModel;
    private $entrevistaModel;

    public function __construct()
    {
        $this->empresaModel = new Empresa();
        $this->vagaModel = new Vaga();
        $this->entrevistaModel = new Entrevista();
    }

    public function index()
    {
        $busca = $this->getQuery('busca');
        
        if ($busca) {
            $empresas = $this->empresaModel->buscarEmpresas($busca);
        } else {
            $empresas = $this->empresaModel->findAll();
        }

        return $this->render('empresa/index', [
            'empresas' => $empresas,
            'busca' => $busca
        ]);
    }

    public function show($id)
    {
        $empresa = $this->empresaModel->findById($id);
        
        if (!$empresa) {
            $this->redirect('/empresas');
        }

        $vagas = $this->empresaModel->getVagasEmpresa($id, true);
        
        return $this->render('empresa/show', [
            'empresa' => $empresa,
            'vagas' => $vagas,
            'logo' => $this->empresaModel->getResumoLogo($id)
        ]);
    }

    /** RF07 / UC-E04 — perfil da empresa. */
    public function perfil()
    {
        $this->requirePerfil('empresa');

        return $this->renderPerfil($this->getSession('user_id'));
    }

    private function renderPerfil($empresaId, $extra = [])
    {
        return $this->render('empresa/perfil', array_merge([
            'empresa' => $this->empresaModel->findById($empresaId),
            'logo' => $this->empresaModel->getResumoLogo($empresaId),
        ], $extra));
    }

    public function atualizarPerfil()
    {
        $this->requirePerfil('empresa');

        if (!$this->isPost()) {
            $this->redirect('/empresa/perfil');
        }

        $empresaId = $this->getSession('user_id');
        $data = [
            'nome' => $this->getPost('nome'),
            'responsavel' => $this->getPost('responsavel'),
            'telefone' => $this->getPost('telefone'),
            'campos_endereco' => Endereco::campos($_POST),
            'descricao' => $this->getPost('descricao'),
            'setor' => $this->getPost('setor'),
            'website' => $this->getPost('website'),
            'nova_senha' => (string) $this->getPost('nova_senha', '')
        ];

        $erros = $this->empresaModel->validarPerfil($data);
        if ($erros) {
            unset($data['nova_senha']);
            return $this->renderPerfil($empresaId, [
                'empresa' => array_merge($this->empresaModel->findById($empresaId), $data),
                'erros' => $erros
            ]);
        }

        $this->empresaModel->atualizarPerfil($empresaId, $data);
        $this->setSession('user_name', trim($data['nome']));
        $this->redirect('/empresa/perfil?status=perfil_atualizado');
    }

    /** RF07 — envio do logo da empresa. */
    public function enviarLogo()
    {
        $this->requirePerfil('empresa');

        if (!$this->isPost()) {
            $this->redirect('/empresa/perfil');
        }

        $empresaId = $this->getSession('user_id');
        $erro = $this->empresaModel->salvarLogo($empresaId, $_FILES['logo'] ?? null);
        if ($erro !== null) {
            return $this->renderPerfil($empresaId, ['erroLogo' => $erro]);
        }

        $this->redirect('/empresa/perfil?status=logo_enviado');
    }

    /** Logo da empresa: imagem pública, exibida na página da empresa. */
    public function logo($id)
    {
        $this->enviarConteudo($this->empresaModel->getLogo($id));
    }

    /** RF13 — a empresa baixa o currículo de quem se candidatou a uma vaga dela (RN21). */
    public function baixarCurriculoCandidato($vagaId, $candidatoId)
    {
        $this->requirePerfil('empresa');

        $candidato = $this->empresaModel->getCurriculoDeCandidato($this->getSession('user_id'), $vagaId, $candidatoId);
        $this->enviarConteudo($candidato, 'curriculo_' . ($candidato['nome'] ?? ''));
    }

    /** RF09 — a empresa baixa a carta de apresentação de quem se candidatou a uma vaga dela (RN21). */
    public function baixarCartaCandidato($vagaId, $candidatoId)
    {
        $this->requirePerfil('empresa');

        $carta = $this->empresaModel->getCartaDeCandidato($this->getSession('user_id'), $vagaId, $candidatoId);
        $this->enviarConteudo($carta, 'carta_' . ($carta['nome'] ?? ''));
    }

    /** RN19 / UC02 Gerar Relatório de Estatísticas de Vagas. */
    public function relatorio()
    {
        $this->requirePerfil('empresa');

        $filtros = [
            'status' => (string) $this->getQuery('status', ''),
            'de' => (string) $this->getQuery('de', ''),
            'ate' => (string) $this->getQuery('ate', ''),
        ];

        return $this->render('empresa/relatorio', [
            'relatorio' => $this->empresaModel->getRelatorioVagas($this->getSession('user_id'), $filtros),
            'filtros' => $filtros
        ]);
    }

    public function painel()
    {
        $this->requirePerfil('empresa');
        
        $empresaId = $this->getSession('user_id');
        $empresa = $this->empresaModel->findById($empresaId);
        $vagas = $this->empresaModel->getVagasEmpresa($empresaId);
        
        return $this->render('empresa/painel', [
            'empresa' => $empresa,
            'vagas' => $vagas
        ]);
    }

    public function minhasVagas()
    {
        $this->requirePerfil('empresa');
        
        $empresaId = $this->getSession('user_id');
        $vagas = $this->empresaModel->getVagasEmpresa($empresaId);
        
        return $this->render('empresa/minhas_vagas', [
            'vagas' => $vagas
        ]);
    }

    public function candidatosVaga($vagaId)
    {
        $this->requirePerfil('empresa');
        
        $empresaId = $this->getSession('user_id');
        $vaga = $this->vagaModel->findById($vagaId);
        
        // Verifica se a vaga pertence à empresa
        if (!$vaga || $vaga['empresa_id'] != $empresaId) {
            $this->redirect('/painel-empresa');
        }
        
        return $this->renderCandidatosVaga($vaga, $empresaId);
    }

    private function renderCandidatosVaga($vaga, $empresaId, $extra = [])
    {
        return $this->render('empresa/candidatos_vaga', array_merge([
            'vaga' => $vaga,
            'candidatos' => $this->empresaModel->getCandidatosPorVaga($vaga['id'], $empresaId),
            'entrevistas' => $this->entrevistaModel->getPorVaga($vaga['id'], $empresaId),
        ], $extra));
    }

    /** RF26 / UC-E06 Agendar Entrevista (e o cenário alternativo de reagendamento). */
    public function agendarEntrevista()
    {
        $this->requirePerfil('empresa');

        if (!$this->isPost()) {
            $this->redirect('/painel-empresa');
        }

        $empresaId = $this->getSession('user_id');
        $dados = [
            'vaga_id' => (int) $this->getPost('vaga_id'),
            'candidato_id' => (int) $this->getPost('candidato_id'),
            'data_hora' => $this->getPost('data_hora'),
            'local' => $this->getPost('local'),
        ];

        $vaga = $this->vagaModel->findById($dados['vaga_id']);
        if (!$vaga || $vaga['empresa_id'] != $empresaId) {
            $this->redirect('/painel-empresa');
        }

        $erros = $this->entrevistaModel->agendamento($empresaId, $dados);
        if ($erros) {
            return $this->renderCandidatosVaga($vaga, $empresaId, [
                'errosEntrevista' => $erros,
                'dadosEntrevista' => $dados,
            ]);
        }

        $this->redirect("/empresa/vagas/{$vaga['id']}/candidatos?status=entrevista_agendada");
    }

    public function cancelarEntrevista()
    {
        $this->encerrarEntrevista('cancelar', 'entrevista_cancelada');
    }

    public function finalizarEntrevista()
    {
        $this->encerrarEntrevista('finalizar', 'entrevista_realizada');
    }

    private function encerrarEntrevista($operacao, $statusOk)
    {
        $this->requirePerfil('empresa');

        if (!$this->isPost()) {
            $this->redirect('/painel-empresa');
        }

        $vagaId = (int) $this->getPost('vaga_id');
        $ok = $this->entrevistaModel->$operacao($this->getSession('user_id'), (int) $this->getPost('entrevista_id'));

        $this->redirect("/empresa/vagas/{$vagaId}/candidatos?" . ($ok ? "status={$statusOk}" : 'error=entrevista_nao_alterada'));
    }

    public function atualizarStatusCandidato()
    {
        $this->requirePerfil('empresa');
        
        if (!$this->isPost()) {
            $this->redirect('/painel-empresa');
        }
        
        $candidatoId = $this->getPost('candidato_id');
        $vagaId = $this->getPost('vaga_id');
        $status = $this->getPost('status');
        
        $this->empresaModel->atualizarStatusCandidatura($candidatoId, $vagaId, $status, $this->getSession('user_id'));
        $this->redirect("/empresa/vagas/{$vagaId}/candidatos?status=status_atualizado");
    }

    public function removerCandidato()
    {
        $this->requirePerfil('empresa');
        
        if (!$this->isPost()) {
            $this->redirect('/painel-empresa');
        }
        
        $candidatoId = $this->getPost('candidato_id');
        $vagaId = $this->getPost('vaga_id');
        
        $this->empresaModel->removerCandidato($candidatoId, $vagaId, $this->getSession('user_id'));
        $this->redirect("/empresa/vagas/{$vagaId}/candidatos?status=candidato_removido");
    }
} 