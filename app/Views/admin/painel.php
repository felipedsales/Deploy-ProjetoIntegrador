<?php
$title = 'Painel Administrativo - Ferraz Conecta';
$cartoes = [
    ['Denúncias em aberto', $resumo['denuncias_abertas'] ?? 0, '/admin/denuncias', 'fa-flag text-danger'],
    ['Vagas ativas', ($resumo['vagas_ativas'] ?? 0) . ' de ' . ($resumo['vagas'] ?? 0), '/admin/vagas', 'fa-briefcase text-primary'],
    ['Candidatos', $resumo['candidatos'] ?? 0, '/admin/usuarios', 'fa-user text-success'],
    ['Empresas', $resumo['empresas'] ?? 0, '/admin/usuarios', 'fa-building text-warning'],
    ['Contas suspensas', $resumo['contas_suspensas'] ?? 0, '/admin/usuarios', 'fa-user-slash text-secondary'],
];
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-user-shield text-primary"></i> Painel Administrativo</h2>

    <?php include __DIR__ . '/_avisos.php'; ?>

    <div class="row">
        <?php foreach ($cartoes as [$rotulo, $valor, $link, $icone]): ?>
            <div class="col-md-4 mb-3">
                <a href="<?= $link ?>" class="text-decoration-none">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-muted mb-1"><i class="fas <?= $icone ?>"></i> <?= $rotulo ?></p>
                            <p class="h3 mb-0 text-dark"><?= htmlspecialchars((string) $valor) ?></p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
