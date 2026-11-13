<?php
$title = 'Usuários - Painel Administrativo - Ferraz Conecta';
$grupos = [
    ['Candidatos', 'candidato', $candidatos ?? []],
    ['Empresas', 'empresa', $empresas ?? []],
];
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-users text-primary"></i> Usuários</h2>

    <?php include __DIR__ . '/_avisos.php'; ?>

    <?php foreach ($grupos as [$rotulo, $tipo, $contas]): ?>
        <h4 class="mt-4"><?= $rotulo ?></h4>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <?php if ($tipo === 'empresa'): ?><th>Vagas</th><?php endif; ?>
                        <th>Cadastro</th>
                        <th>Situação</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contas as $conta): ?>
                        <tr>
                            <td><?= htmlspecialchars($conta['nome']) ?></td>
                            <td><?= htmlspecialchars($conta['email']) ?></td>
                            <?php if ($tipo === 'empresa'): ?><td><?= (int) $conta['total_vagas'] ?></td><?php endif; ?>
                            <td><?= date('d/m/Y', strtotime($conta['created_at'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $conta['status'] === 'Ativa' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($conta['status']) ?></span>
                            </td>
                            <td class="text-end">
                                <?php if ($conta['status'] === 'Ativa'): ?>
                                    <form method="POST" action="/admin/usuarios/suspender"
                                        onsubmit="return confirm('Suspender esta conta?<?= $tipo === 'empresa' ? ' As vagas ativas da empresa também saem do ar.' : '' ?>')">
                                        <input type="hidden" name="tipo" value="<?= $tipo ?>">
                                        <input type="hidden" name="usuario_id" value="<?= $conta['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Suspender</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/admin/usuarios/reativar">
                                        <input type="hidden" name="tipo" value="<?= $tipo ?>">
                                        <input type="hidden" name="usuario_id" value="<?= $conta['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success">Reativar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($contas)): ?>
                        <tr><td colspan="6" class="text-muted">Nenhum cadastro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>
