<?php

/**
 * Bootstrap da aplicação Ferraz Conecta.
 *
 * Ponto único de inicialização, incluído no topo de todo ponto de entrada
 * (public/index.php, scripts de linha de comando, testes). Ele:
 *   - registra o autoload do Composer;
 *   - carrega os helpers de infraestrutura;
 *   - lê o arquivo .env UMA ÚNICA VEZ.
 *
 * Em produção (Railway) normalmente não existe arquivo .env: as variáveis vêm
 * do ambiente do container. Por isso:
 *   - safeLoad()        não falha se o .env não existir;
 *   - createImmutable() não sobrescreve variáveis já definidas no ambiente.
 */

if (defined('FERRAZ_CONECTA_BOOTSTRAP')) {
    return;
}
define('FERRAZ_CONECTA_BOOTSTRAP', true);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/helpers.php';

$raizProjeto = dirname(__DIR__);

if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable($raizProjeto)->safeLoad();
} elseif (is_file($raizProjeto . '/.env')) {
    // O pacote ainda não foi instalado, mas há um .env local esperando para ser lido.
    error_log('[Ferraz Conecta] vlucas/phpdotenv nao instalado: o arquivo .env NAO foi carregado. Rode "composer install".');
}

// Exibição de erros segue o ambiente (APP_ENV / APP_DEBUG via
// ferraz_ambiente_desenvolvimento()), nunca o padrão do php.ini do container:
// em produção o container sobe com display_errors ligado por padrão, o que
// vazava warning/notice na página. Log de erro fica sempre ativo, nos dois
// ambientes.
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', ferraz_ambiente_desenvolvimento() ? '1' : '0');
ini_set('display_startup_errors', ferraz_ambiente_desenvolvimento() ? '1' : '0');
