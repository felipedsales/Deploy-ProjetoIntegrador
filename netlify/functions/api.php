<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Router;

// Configurar headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar sessão
session_start();

// Obter dados da requisição
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remover o prefixo /api da URI
$uri = str_replace('/api', '', $uri);

// Criar roteador
$router = new Router();

// Configurar rotas da API
$router->get('/vagas', 'VagaController@index');
$router->get('/vagas/{id}', 'VagaController@show');
$router->post('/vagas', 'VagaController@create');
$router->put('/vagas/{id}', 'VagaController@edit');
$router->delete('/vagas/{id}', 'VagaController@delete');

$router->get('/empresas', 'EmpresaController@index');
$router->get('/empresas/{id}', 'EmpresaController@show');

$router->post('/auth/login', 'AuthController@login');
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/logout', 'AuthController@logout');

$router->get('/candidatos/minhas-candidaturas', 'CandidatoController@minhasCandidaturas');
$router->get('/candidatos/perfil', 'CandidatoController@perfil');

// Despachar a requisição
try {
    $router->dispatch($method, $uri);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} 