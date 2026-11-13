<?php

/**
 * Cria um administrador pela linha de comando (RF03).
 *
 * O painel só deixa um administrador cadastrar outro, então o primeiro precisa
 * nascer por aqui. Os dados vêm das variáveis de ambiente — nada de credencial
 * no código (RNF03):
 *
 *     ADMIN_NOME="Maria Souza" ADMIN_EMAIL=maria@exemplo.gov.br ADMIN_SENHA='...' \
 *         php database/criar_administrador.php
 *
 * Se já existir administrador com o e-mail informado, não faz nada. No Railway
 * o docker/entrypoint.sh chama este script a cada start quando ADMIN_EMAIL
 * está definida; depois que a conta existir, as três variáveis podem (e devem)
 * ser removidas do painel do serviço.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Administrador;

$dados = [
    'nome' => ferraz_env('ADMIN_NOME', 'Administrador'),
    'email' => ferraz_env('ADMIN_EMAIL'),
    'senha' => ferraz_env('ADMIN_SENHA'),
];

if (!$dados['email'] || !$dados['senha']) {
    fwrite(STDERR, "Defina ADMIN_EMAIL e ADMIN_SENHA (e, se quiser, ADMIN_NOME).\n");
    exit(1);
}

$administrador = new Administrador();

if ($administrador->buscarPorEmail($dados['email'])) {
    echo "Administrador {$dados['email']} já existe; nada a fazer.\n";
    exit(0);
}

$erros = $administrador->validar($dados);
if ($erros) {
    fwrite(STDERR, "Dados inválidos: " . implode(' ', $erros) . "\n");
    exit(1);
}

$administrador->cadastrar($dados);
echo "Administrador {$dados['email']} criado.\n";
