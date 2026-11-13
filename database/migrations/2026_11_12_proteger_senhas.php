<?php

/**
 * Migration de dados (RNF03): converte em hash as senhas gravadas em texto puro.
 *
 * Até esta versão o sistema gravava e comparava a senha em texto puro. O código
 * novo só aceita hash (password_hash / password_verify), então as contas já
 * existentes precisam ser convertidas para continuarem conseguindo entrar.
 *
 * Uso (linha de comando, na raiz do projeto):
 *
 *     php database/migrations/2026_11_12_proteger_senhas.php
 *
 * Pode rodar quantas vezes for preciso: só altera a senha que ainda não é um
 * hash reconhecido pelo PHP. No Railway é executado pelo docker/entrypoint.sh
 * a cada start do container.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Models\Database;

$db = Database::getInstance();
$total = 0;

foreach (['candidatos', 'empresas', 'administradores'] as $tabela) {
    // A tabela administradores só existe depois da migration do escopo da v1.
    $existe = $db->fetch(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
        [$tabela]
    );
    if (!$existe) {
        continue;
    }

    $convertidas = 0;
    foreach ($db->fetchAll("SELECT id, senha FROM {$tabela}") as $conta) {
        $senha = (string) $conta['senha'];
        if ($senha === '' || password_get_info($senha)['algo'] !== null) {
            continue; // vazia ou já é hash
        }

        // Atualiza só se a senha ainda for a que foi lida, e sem mexer em updated_at.
        $db->query(
            "UPDATE {$tabela} SET senha = ?, updated_at = updated_at WHERE id = ? AND senha = ?",
            [password_hash($senha, PASSWORD_DEFAULT), $conta['id'], $senha]
        );
        $convertidas++;
    }

    echo "{$tabela}: {$convertidas} senha(s) convertida(s)\n";
    $total += $convertidas;
}

echo "Total: {$total}\n";
