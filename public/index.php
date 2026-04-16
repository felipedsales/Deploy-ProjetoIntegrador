<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Router;
use App\Models\Database;

// Inicia a sessão
session_start();

// Cria o roteador
$router = new Router();

// --- ROTA DE SETUP DO BANCO DE DADOS (TEMPORÁRIA) ---
$router->get('/setup-database-agora-vai', function() {
    try {
        echo "<h1>Iniciando setup do banco de dados...</h1>";
        
        $pdo = Database::getInstance()->getConnection();
        $sqlFile = __DIR__ . '/../database/schema.sql';

        if (!file_exists($sqlFile)) {
            throw new \Exception("Arquivo schema.sql não encontrado em {$sqlFile}");
        }

        $sql = file_get_contents($sqlFile);
        
        // Executa o script SQL
        $pdo->exec($sql);

        echo "<h2>SUCESSO!</h2>";
        echo "<p>O banco de dados foi configurado e as tabelas foram criadas.</p>";
        echo "<p><strong>IMPORTANTE:</strong> Agora remova esta rota do arquivo 'public/index.php' por segurança!</p>";

    } catch (\Exception $e) {
        echo "<h2>ERRO DURANTE O SETUP:</h2>";
        echo "<pre style='background-color: #f0f0f0; padding: 10px; border: 1px solid red;'>";
        echo "Erro: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . "\n";
        echo "Linha: " . $e->getLine() . "\n";
        echo "</pre>";
    }
    return null;
});


// Rota de depuração temporária
$router->get('/debug-env', function() {
    header('Content-Type: text/plain');
    echo "--- Debugging Environment Variables ---\n\n";
    echo "DB_TYPE: " . (getenv('DB_TYPE') ?: 'NOT SET') . "\n";
    echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
    echo "DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET') . "\n";
    echo "DB_DATABASE: " . (getenv('DB_DATABASE') ?: 'NOT SET') . "\n";
    echo "DB_USERNAME: " . (getenv('DB_USERNAME') ?: 'NOT SET') . "\n";
    echo "DB_PASSWORD: " . (getenv('DB_PASSWORD') ? 'SET (hidden)' : 'NOT SET') . "\n";
    return null;
});


// Rota de teste
$router->get('/test', 'HomeController@test');
$router->get('/test-footer', 'HomeController@testFooter');

// Rotas da aplicação
$router->get('/', 'HomeController@index');
$router->get('/sobre', 'HomeController@sobre');

// Rotas de empresas (públicas)
$router->get('/empresas', 'EmpresaController@index');
$router->get('/empresas/{id}', 'EmpresaController@show');

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
$router->get('/auth/google', 'AuthController@googleAuth');
$router->get('/auth/google/callback', 'AuthController@googleCallback');
$router->get('/auth/linkedin', 'AuthController@linkedinAuth');
$router->get('/auth/linkedin/callback', 'AuthController@linkedinCallback');

// Rotas de vagas
$router->get('/vagas', 'VagaController@index');
$router->get('/vagas/criar', 'VagaController@create');
$router->post('/vagas/criar', 'VagaController@create');
$router->post('/vagas/candidatar', 'VagaController@candidatar');
$router->post('/vagas/desistir', 'VagaController@desistir');
$router->post('/vagas/denunciar', 'VagaController@denunciar');
$router->get('/vagas/{id}', 'VagaController@show');
$router->get('/vagas/{id}/editar', 'VagaController@edit');
$router->post('/vagas/{id}/editar', 'VagaController@edit');
$router->post('/vagas/{id}/excluir', 'VagaController@delete');

// Rotas de candidatos
$router->get('/minhas-candidaturas', 'CandidatoController@minhasCandidaturas');
$router->get('/perfil', 'CandidatoController@perfil');
$router->post('/perfil/atualizar', 'CandidatoController@atualizarPerfil');

// Rotas de empresas (privadas)
$router->get('/painel-empresa', 'EmpresaController@painel');
$router->get('/empresa/vagas', 'EmpresaController@minhasVagas');
$router->get('/empresa/vagas/{id}/candidatos', 'EmpresaController@candidatosVaga');
$router->post('/empresa/candidatos/status', 'EmpresaController@atualizarStatusCandidato');
$router->post('/empresa/candidatos/remover', 'EmpresaController@removerCandidato');

// --- Rotas da API ---
$router->get('/api/vagas', 'VagaController@apiIndex');


// Despacha a requisição
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
