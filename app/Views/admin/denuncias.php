<?php
$title = 'Denúncias - Ferraz Conecta';
$motivos = [
    'conteudo_inadequado' => 'Conteúdo inadequado',
    'informacoes_falsas' => 'Informações falsas',
    'discriminacao' => 'Discriminação',
    'outro' => 'Outro',
];
$cores = ['Pendente' => 'warning', 'Em análise' => 'info', 'Resolvida' => 'success', 'Descartada' => 'secondary'];
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-flag text-danger"></i> Denúncias</h2>

    <?php include __DIR__ . '/_avisos.php'; ?>

    <?php if (empty($denuncias)): ?>
        <p class="text-muted">Nenhuma denúncia recebida.</p>
    <?php endif; ?>

    <?php foreach ($denuncias as $denuncia): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title mb-1">
                        <?php if ($denuncia['vaga_titulo'] !== null): ?>
                            <?= htmlspecialchars($denuncia['vaga_titulo']) ?>
                            <small class="text-muted">— <?= htmlspecialchars($denuncia['empresa_nome'] ?? '') ?></small>
                        <?php else: ?>
                            <span class="text-muted">Vaga removida</span>
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-<?= $cores[$denuncia['status']] ?? 'secondary' ?>"><?= htmlspecialchars($denuncia['status']) ?></span>
                </div>

                <p class="mb-1">
                    <strong>Motivo:</strong> <?= htmlspecialchars($motivos[$denuncia['motivo']] ?? $denuncia['motivo']) ?>
                    <?php if ($denuncia['vaga_titulo'] !== null): ?>
                        · <strong>Situação da vaga:</strong> <?= htmlspecialchars($denuncia['vaga_status']) ?>
                    <?php endif; ?>
                </p>
                <?php if (!empty($denuncia['descricao'])): ?>
                    <p class="mb-1"><?= nl2br(htmlspecialchars($denuncia['descricao'])) ?></p>
                <?php endif; ?>
                <p class="text-muted small mb-3">
                    Enviada por <?= htmlspecialchars($denuncia['candidato_nome'] ?? 'candidato removido') ?>
                    em <?= date('d/m/Y H:i', strtotime($denuncia['created_at'])) ?>
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="/admin/denuncias/status" class="d-flex gap-2">
                        <input type="hidden" name="denuncia_id" value="<?= $denuncia['id'] ?>">
                        <select name="status" class="form-select form-select-sm" aria-label="Situação da denúncia">
                            <?php foreach (array_keys($cores) as $status): ?>
                                <option value="<?= $status ?>" <?= $denuncia['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">Salvar situação</button>
                    </form>

                    <?php if ($denuncia['vaga_titulo'] !== null && $denuncia['vaga_status'] !== 'Inativa'): ?>
                        <form method="POST" action="/admin/vagas/inativar"
                            onsubmit="return confirm('Tirar esta vaga do ar?')">
                            <input type="hidden" name="vaga_id" value="<?= $denuncia['vaga_id'] ?>">
                            <input type="hidden" name="origem" value="denuncias">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Inativar vaga</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
