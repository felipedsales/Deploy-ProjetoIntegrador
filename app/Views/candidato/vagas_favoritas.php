<?php
$title = 'Vagas Favoritas - Ferraz Conecta';
?>

<div class="container mt-4">
    <h2 class="mb-4">
        <i class="fas fa-star text-warning"></i> Minhas Vagas Favoritas
    </h2>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'favorito_removido'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Vaga removida dos seus favoritos.
        </div>
    <?php endif; ?>

    <?php if (!empty($vagas)): ?>
        <div class="cards-container">
            <?php foreach ($vagas as $vaga): ?>
                <div class="card">
                    <div class="card-body">
                        <h3><?= htmlspecialchars($vaga['titulo'] ?? '') ?></h3>

                        <?php if (empty($vaga['disponivel'])): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Esta vaga não está mais disponível.
                            </div>
                        <?php endif; ?>

                        <ul class="details">
                            <li><strong>Empresa:</strong> <?= htmlspecialchars($vaga['empresa_nome'] ?? 'Não informado') ?></li>
                            <li><strong>Salário:</strong> <?= $this->formatMoney($vaga['salario'] ?? null) ?></li>
                            <li><strong>Localização:</strong> <?= htmlspecialchars($vaga['localizacao'] ?? 'Não informado') ?></li>
                            <li><strong>Tipo de Contrato:</strong> <?= htmlspecialchars($vaga['tipo_contrato'] ?? 'Não informado') ?></li>
                            <li><strong>Modalidade:</strong> <?= htmlspecialchars($vaga['modalidade'] ?? 'Não informado') ?></li>
                            <?php if (!empty($vaga['disponivel'])): ?>
                                <li><strong>Candidaturas até:</strong> <?= date('d/m/Y', strtotime($vaga['data_expiracao'])) ?></li>
                            <?php endif; ?>
                        </ul>

                        <div class="mt-3">
                            <div class="btn-group" role="group">
                                <?php if (!empty($vaga['disponivel'])): ?>
                                    <a href="/vagas/<?= $vaga['id'] ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> Ver Vaga
                                    </a>
                                <?php endif; ?>

                                <form method="POST" action="/vagas/desfavoritar" class="d-inline">
                                    <input type="hidden" name="vaga_id" value="<?= $vaga['id'] ?>">
                                    <input type="hidden" name="origem" value="lista">
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="fas fa-times"></i> Remover
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="far fa-star fa-4x text-muted mb-4"></i>
            <h3>Nenhuma vaga favorita ainda</h3>
            <p class="text-muted mb-4">Favorite as vagas que interessam para encontrá-las aqui depois.</p>
            <a href="/vagas" class="btn btn-primary btn-lg">
                <i class="fas fa-search"></i> Procurar Vagas
            </a>
        </div>
    <?php endif; ?>
</div>
