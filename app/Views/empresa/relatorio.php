<?php
$title = 'Relatório de Vagas - Ferraz Conecta';
$totais = $relatorio['totais'];
$taxa = function ($valor) {
    return $valor === null ? '—' : number_format($valor, 1, ',', '.') . '%';
};
?>

<div class="container mt-4">
    <h2 class="mb-2"><i class="fas fa-chart-bar text-primary"></i> Relatório de Estatísticas de Vagas</h2>
    <p class="text-muted">Somente as vagas da sua empresa. Taxa de retorno = candidaturas ÷ visualizações.</p>

    <form method="GET" action="/empresa/relatorio" class="row g-2 align-items-end mb-4">
        <div class="col-md-3">
            <label for="status" class="form-label small mb-1">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Todos</option>
                <?php foreach (['Ativa', 'Pausada', 'Inativa'] as $status): ?>
                    <option value="<?= $status ?>" <?= ($filtros['status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="de" class="form-label small mb-1">Publicadas a partir de</label>
            <input type="date" class="form-control" id="de" name="de" value="<?= htmlspecialchars($filtros['de'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label for="ate" class="form-label small mb-1">Publicadas até</label>
            <input type="date" class="form-control" id="ate" name="ate" value="<?= htmlspecialchars($filtros['ate'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/empresa/relatorio" class="btn btn-outline-secondary">Limpar</a>
        </div>
    </form>

    <div class="row mb-4">
        <?php foreach ([['Vagas', $totais['vagas']], ['Visualizações', $totais['visualizacoes']], ['Candidaturas', $totais['candidaturas']], ['Taxa de retorno', $taxa($totais['taxa_retorno'])]] as [$rotulo, $valor]): ?>
            <div class="col-6 col-md-3 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1"><?= $rotulo ?></p>
                        <p class="h3 mb-0"><?= htmlspecialchars((string) $valor) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Vaga</th>
                    <th>Status</th>
                    <th>Publicada em</th>
                    <th>Candidaturas até</th>
                    <th class="text-end">Visualizações</th>
                    <th class="text-end">Candidaturas</th>
                    <th class="text-end">Taxa de retorno</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($relatorio['vagas'] as $vaga): ?>
                    <tr>
                        <td><a href="/empresa/vagas/<?= $vaga['id'] ?>/candidatos"><?= htmlspecialchars($vaga['titulo']) ?></a></td>
                        <td><?= htmlspecialchars($vaga['status']) ?></td>
                        <td><?= date('d/m/Y', strtotime($vaga['created_at'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($vaga['data_expiracao'])) ?></td>
                        <td class="text-end"><?= (int) $vaga['visualizacoes'] ?></td>
                        <td class="text-end"><?= (int) $vaga['candidaturas'] ?></td>
                        <td class="text-end"><?= $taxa($vaga['taxa_retorno']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($relatorio['vagas'])): ?>
                    <tr><td colspan="7" class="text-muted">Nenhuma vaga encontrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="/painel-empresa" class="btn btn-outline-secondary">Voltar ao Painel</a>
</div>
