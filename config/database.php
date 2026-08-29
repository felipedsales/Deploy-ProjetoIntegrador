<?php

/**
 * Configuração de acesso ao banco de dados (MySQL 8).
 *
 * TODAS as credenciais vêm de variáveis de ambiente (RNF03) — nada fica
 * hardcoded. Em desenvolvimento elas são lidas do arquivo .env (carregado
 * uma única vez em app/bootstrap.php via phpdotenv); em produção, do ambiente
 * do serviço no Railway.
 *
 * Variáveis lidas:
 *   DB_CONNECTION  driver PDO      (opcional, padrão: mysql)
 *   DB_HOST        host do banco   (obrigatória)
 *   DB_PORT        porta           (opcional, padrão: 3306)
 *   DB_NAME        nome do banco   (obrigatória)
 *   DB_USER        usuário         (obrigatória)
 *   DB_PASS        senha           (obrigatória; o valor pode ser vazio)
 *
 * O objeto PDO propriamente dito — com prepared statements (ATTR_EMULATE_PREPARES
 * = false) e charset utf8mb4 — é montado em App\Models\Database a partir deste
 * array. Este arquivo apenas fornece a configuração.
 */

require_once __DIR__ . '/../app/bootstrap.php';

$obrigatorias = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
$valores = [];
$faltando = [];

foreach ($obrigatorias as $nome) {
    $valores[$nome] = ferraz_env($nome);
    if ($valores[$nome] === null) {
        $faltando[] = $nome;
    }
}

if ($faltando !== []) {
    $lista = implode(', ', $faltando);

    if (ferraz_ambiente_desenvolvimento()) {
        throw new RuntimeException(
            'Configuração do banco de dados incompleta: defina ' . $lista .
            ' no arquivo .env (use o .env.example como modelo).'
        );
    }

    // Produção: registra o detalhe no log do servidor e devolve mensagem genérica.
    error_log('[Ferraz Conecta] Variaveis de banco ausentes: ' . $lista);
    throw new RuntimeException('Erro de configuração do servidor. Contate o administrador do sistema.');
}

return [
    'type'     => strtolower((string) ferraz_env('DB_CONNECTION', 'mysql')),
    'host'     => $valores['DB_HOST'],
    'port'     => (int) ferraz_env('DB_PORT', 3306),
    'database' => $valores['DB_NAME'],
    'username' => $valores['DB_USER'],
    'password' => $valores['DB_PASS'],
    'charset'  => 'utf8mb4',
];
