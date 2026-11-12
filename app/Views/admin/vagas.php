<?php
$title = 'Vagas - Painel Administrativo - Ferraz Conecta';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-briefcase text-primary"></i> Vagas</h2>

    <?php include __DIR__ . '/_avisos.php'; ?>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Vaga</th>
                    <th>Empresa</th>
                    <th>Situação</th>
                    <th>Candidaturas até</th>
                    <th>Denúncias</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vagas as $vaga): ?>
                    <tr>
                        <td><?= htmlspecialchars($vaga['titulo']) ?></td>
                        <td><?= htmlspecialchars($vaga['empresa_nome']) ?></td>
                        <td><?= htmlspecialchars($vaga['status']) ?></td>
                        <td>
                            <?= date('d/m/Y', strtotime($vaga['data_expiracao'])) ?>
                            <?php if ($vaga['data_expiracao'] < date('Y-m-d')): ?>
                                <span class="badge bg-secondary">Prazo encerrado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $vaga['total_denuncias'] ?></td>
                        <td class="text-end">
                            <form method="POST" action="/admin/vagas/<?= $vaga['status'] === 'Inativa' ? 'reativar' : 'inativar' ?>">
                                <input type="hidden" name="vaga_id" value="<?= $vaga['id'] ?>">
                                <?php if ($vaga['status'] === 'Inativa'): ?>
                                    <button type="submit" class="btn btn-sm btn-outline-success">Reativar</button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Inativar</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($vagas)): ?>
                    <tr><td colspan="6" class="text-muted">Nenhuma vaga cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
