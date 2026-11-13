<?php

/**
 * Atualiza a estrutura de um banco já existente para a do database/schema.sql.
 *
 * Banco novo não precisa deste script: basta importar database/schema.sql.
 * Banco que já estava em uso (produção) é atualizado por aqui, sem cliente
 * MySQL: o docker/entrypoint.sh executa este script a cada start do container.
 *
 * Pode rodar quantas vezes for preciso. Cada passo confere antes se já foi
 * aplicado e só altera o que falta, então um start interrompido no meio é
 * retomado no start seguinte. Nenhum passo apaga dado: as duas colunas
 * antigas de caminho de arquivo só são removidas se estiverem vazias.
 *
 * Uso manual, na raiz do projeto:
 *
 *     php database/migrar.php
 *
 * Atende: RN14, RF03, RF16, RN07, RN18, RF19, RF21, RF26, RF08, RF07.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Database;

$db = Database::getInstance();

$existeTabela = function ($tabela) use ($db) {
    return (bool) $db->fetch(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
        [$tabela]
    );
};
$coluna = function ($tabela, $coluna) use ($db) {
    return $db->fetch(
        'SELECT is_nullable AS nulo, column_type AS tipo FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        [$tabela, $coluna]
    );
};
$existeIndice = function ($tabela, $indice) use ($db) {
    return (bool) $db->fetch(
        'SELECT 1 FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
        [$tabela, $indice]
    );
};
$aplicados = 0;
$aplicar = function ($descricao, $sql) use ($db, &$aplicados) {
    $db->getConnection()->exec($sql);
    echo "  aplicado: {$descricao}\n";
    $aplicados++;
};

// Só atualiza banco que já tem a estrutura original. Banco vazio se cria com schema.sql.
if (!$existeTabela('candidatos') || !$existeTabela('empresas') || !$existeTabela('vagas')) {
    echo "Banco sem as tabelas base: importe database/schema.sql. Nada a migrar.\n";
    exit(0);
}

echo "Conferindo a estrutura do banco...\n";

// 1) Tabelas novas: as definições vêm do próprio schema.sql (CREATE TABLE IF NOT
//    EXISTS), para não existirem duas cópias da mesma estrutura.
$schema = preg_replace('/^\s*--.*$/m', '', file_get_contents(__DIR__ . '/schema.sql'));
foreach (array_filter(array_map('trim', explode(';', $schema))) as $comando) {
    if (preg_match('/^CREATE TABLE IF NOT EXISTS (\w+)/i', $comando, $m) && !$existeTabela($m[1])) {
        $aplicar("tabela {$m[1]}", $comando);
    }
}

// 2) RN14 — prazo de candidatura da vaga. As vagas que já existem recebem 30
//    dias a partir de hoje; depois a coluna passa a ser obrigatória.
$dataExpiracao = $coluna('vagas', 'data_expiracao');
if (!$dataExpiracao) {
    $aplicar('coluna vagas.data_expiracao', 'ALTER TABLE vagas ADD COLUMN data_expiracao DATE NULL AFTER status');
}
if (!$dataExpiracao || $dataExpiracao['nulo'] === 'YES') {
    $aplicar(
        'prazo de 30 dias para as vagas existentes',
        'UPDATE vagas SET data_expiracao = DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), updated_at = updated_at
         WHERE data_expiracao IS NULL'
    );
    $aplicar('vagas.data_expiracao obrigatória', 'ALTER TABLE vagas MODIFY COLUMN data_expiracao DATE NOT NULL');
}
if (!$existeIndice('vagas', 'idx_vagas_expiracao')) {
    $aplicar('índice idx_vagas_expiracao', 'ALTER TABLE vagas ADD KEY idx_vagas_expiracao (data_expiracao)');
}

// 3) RF03 — perfil administrador nas sessões.
if ($existeTabela('sessoes') && strpos($coluna('sessoes', 'tipo_usuario')['tipo'] ?? '', 'administrador') === false) {
    $aplicar(
        "valor 'administrador' em sessoes.tipo_usuario",
        "ALTER TABLE sessoes MODIFY COLUMN tipo_usuario ENUM('candidato', 'empresa', 'administrador') NOT NULL"
    );
}

// 4) RF21 — descrição da denúncia.
if ($existeTabela('denuncias') && !$coluna('denuncias', 'descricao')) {
    $aplicar('coluna denuncias.descricao', 'ALTER TABLE denuncias ADD COLUMN descricao TEXT NULL AFTER motivo');
}

// 5) RN18 — conta ativa ou suspensa.
foreach (['candidatos' => 'endereco', 'empresas' => 'website'] as $tabela => $depoisDe) {
    if (!$coluna($tabela, 'status')) {
        $aplicar(
            "coluna {$tabela}.status",
            "ALTER TABLE {$tabela} ADD COLUMN status ENUM('Ativa', 'Suspensa') NOT NULL DEFAULT 'Ativa' AFTER {$depoisDe}"
        );
    }
}

// 6) RF08 / RF07 — currículos e logos passaram para o banco (tabelas curriculos e
//    logos_empresas). As colunas antigas guardavam o caminho de um arquivo no
//    disco do servidor, que é apagado a cada deploy. Só saem se estiverem vazias.
foreach (['candidatos' => 'curriculo_path', 'empresas' => 'logo_path'] as $tabela => $antiga) {
    if (!$coluna($tabela, $antiga)) {
        continue;
    }
    $preenchidas = $db->fetch("SELECT COUNT(*) AS total FROM {$tabela} WHERE {$antiga} IS NOT NULL AND {$antiga} <> ''");
    if ((int) $preenchidas['total'] > 0) {
        echo "  mantida: {$tabela}.{$antiga} tem {$preenchidas['total']} valor(es); a coluna não é mais usada, mas não foi removida.\n";
        continue;
    }
    $aplicar("remoção da coluna vazia {$tabela}.{$antiga}", "ALTER TABLE {$tabela} DROP COLUMN {$antiga}");
}

echo $aplicados === 0
    ? "Estrutura já estava atualizada.\n"
    : "Estrutura atualizada: {$aplicados} alteração(ões).\n";
