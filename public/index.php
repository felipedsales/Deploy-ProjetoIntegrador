<?php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Router;
use App\Models\Database;

// Inicia a sessão
session_start();

// Cria o roteador
$router = new Router();

// Rota de teste
$router->get('/test', 'HomeController@test');
$router->get('/test-footer', 'HomeController@testFooter');

// Healthcheck de infraestrutura (Railway) — não é RF/RN, ver HealthController
$router->get('/health', 'HealthController@index');

// Rotas da aplicação
$router->get('/', 'HomeController@index');
$router->get('/sobre', 'HomeController@sobre');

// Rotas de empresas (públicas)
$router->get('/empresas', 'EmpresaController@index');
$router->get('/empresas/{id}', 'EmpresaController@show');

// Painel administrativo (RF03, RF16) — só perfil administrador (RN09)
$router->get('/admin/login', 'AdminController@login');
$router->post('/admin/login', 'AdminController@login');
$router->get('/admin', 'AdminController@painel');
$router->get('/admin/denuncias', 'AdminController@denuncias');
$router->post('/admin/denuncias/status', 'AdminController@atualizarDenuncia');
$router->get('/admin/vagas', 'AdminController@vagas');
$router->post('/admin/vagas/inativar', 'AdminController@inativarVaga');
$router->post('/admin/vagas/reativar', 'AdminController@reativarVaga');
$router->get('/admin/usuarios', 'AdminController@usuarios');
$router->post('/admin/usuarios/suspender', 'AdminController@suspenderUsuario');
$router->post('/admin/usuarios/reativar', 'AdminController@reativarUsuario');
$router->get('/admin/administradores', 'AdminController@administradores');
$router->post('/admin/administradores', 'AdminController@cadastrarAdministrador');

// Rotas de autenticação
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@login');
$router->get('/login-empresa', 'AuthController@loginEmpresa');
$router->post('/login-empresa', 'AuthController@loginEmpresa');
$router->get('/cadastro', 'AuthController@register');
$router->post('/cadastro', 'AuthController@register');
$router->get('/cadastro-empresa', 'AuthController@registerEmpresa');
$router->post('/cadastro-empresa', 'AuthController@registerEmpresa');
$router->get('/logout', 'AuthController@logout');

// Rotas de autenticação social
// RF20 (login externo) está fora da v1 — ver docs/IMPLEMENTACOES_FUTURAS.md.
// As rotas /auth/google e /auth/linkedin ficam desligadas até o requisito
// entrar no escopo e as colunas provider/provider_id existirem no schema.

// Rotas de vagas
$router->get('/vagas', 'VagaController@index');
$router->get('/vagas/criar', 'VagaController@create');
$router->post('/vagas/criar', 'VagaController@create');
$router->post('/vagas/candidatar', 'VagaController@candidatar');
$router->post('/vagas/desistir', 'VagaController@desistir');
$router->post('/vagas/denunciar', 'VagaController@denunciar');
$router->post('/vagas/favoritar', 'VagaController@favoritar');
$router->post('/vagas/desfavoritar', 'VagaController@desfavoritar');
$router->get('/vagas/{id}', 'VagaController@show');
$router->get('/vagas/{id}/editar', 'VagaController@edit');
$router->post('/vagas/{id}/editar', 'VagaController@edit');
$router->post('/vagas/{id}/excluir', 'VagaController@delete');

// Rotas de candidatos
$router->get('/minhas-candidaturas', 'CandidatoController@minhasCandidaturas');
$router->get('/vagas-favoritas', 'CandidatoController@vagasFavoritas');
$router->get('/perfil', 'CandidatoController@perfil');
$router->post('/perfil/atualizar', 'CandidatoController@atualizarPerfil');

// Rotas de empresas (privadas)
$router->get('/painel-empresa', 'EmpresaController@painel');
$router->get('/empresa/vagas', 'EmpresaController@minhasVagas');
$router->get('/empresa/vagas/{id}/candidatos', 'EmpresaController@candidatosVaga');
$router->post('/empresa/candidatos/status', 'EmpresaController@atualizarStatusCandidato');
$router->post('/empresa/candidatos/remover', 'EmpresaController@removerCandidato');
$router->post('/empresa/entrevistas/agendar', 'EmpresaController@agendarEntrevista');
$router->post('/empresa/entrevistas/cancelar', 'EmpresaController@cancelarEntrevista');
$router->post('/empresa/entrevistas/finalizar', 'EmpresaController@finalizarEntrevista');

// --- Rotas da API ---
$router->get('/api/vagas', 'VagaController@apiIndex');


// Despacha a requisição
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
