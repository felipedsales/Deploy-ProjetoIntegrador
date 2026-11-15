<?php
$title = 'Logs de Auditoria - Ferraz Conecta';
$consulta = http_build_query(array_filter($filtros) + ['formato' => 'csv']);
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-clipboard-list text-primary"></i> Logs de Auditoria</h2>

    <?php include __DIR__ . '/_avisos.php'; ?>

    <form method="GET" action="/admin/logs" class="row g-2 align-items-end mb-4">
        <div class="col-md-3">
            <label for="administrador_id" class="form-label small mb-1">Administrador</label>
            <select class="form-select" id="administrador_id" name="administrador_id">
                <option value="">Todos</option>
                <?php foreach ($administradores as $administrador): ?>
                    <option value="<?= $administrador['id'] ?>" <?= ($filtros['administrador_id'] ?? '') === (string) $administrador['id'] ? 'selected' : '' ?>><?= htmlspecialchars($administrador['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="acao" class="form-label small mb-1">Ação</label>
            <select class="form-select" id="acao" name="acao">
                <option value="">Todas</option>
                <?php foreach ($acoes as $acao): ?>
                    <option value="<?= htmlspecialchars($acao) ?>" <?= ($filtros['acao'] ?? '') === $acao ? 'selected' : '' ?>><?= htmlspecialchars($acao) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="de" class="form-label small mb-1">De</label>
            <input type="date" class="form-control" id="de" name="de" value="<?= htmlspecialchars($filtros['de'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label for="ate" class="form-label small mb-1">Até</label>
            <input type="date" class="form-control" id="ate" name="ate" value="<?= htmlspecialchars($filtros['ate'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/admin/logs" class="btn btn-outline-secondary">Limpar</a>
        </div>
    </form>

    <p>
        <a href="/admin/logs?<?= htmlspecialchars($consulta) ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
        <span class="text-muted small ms-2"><?= count($logs) ?> registro(s)<?= count($logs) >= 1000 ? ' — exibindo os 1000 mais recentes' : '' ?></span>
    </p>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr><th>Data e hora</th><th>Administrador</th><th>Ação</th><th>Entidade</th><th>Registro</th><th>Detalhes</th></tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-nowrap"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td><?= htmlspecialchars($log['administrador'] ?? 'removido') ?></td>
                        <td><?= htmlspecialchars($log['acao']) ?></td>
                        <td><?= htmlspecialchars($log['entidade']) ?></td>
                        <td><?= htmlspecialchars((string) $log['entidade_id']) ?></td>
                        <td><?= htmlspecialchars((string) $log['detalhes']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-muted">Nenhum registro encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
