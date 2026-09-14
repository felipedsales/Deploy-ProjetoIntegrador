<?php
$title = 'Vagas - Ferraz Conecta';
?>

<section class="search-section">
    <div class="container">
        <h1 class="page-title">Nossas Vagas Abertas</h1>
        <p class="page-subtitle">Encontre a oportunidade ideal para você em Ferraz de Vasconcelos.</p>
        
        <form action="/vagas" method="get" class="search-form">
            <div class="input-group">
                <input type="text" name="busca" class="form-control" placeholder="Digite o cargo ou palavra-chave" value="<?= htmlspecialchars($busca ?? '') ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar Vagas
                </button>
            </div>
        </form>
    </div>
</section>

<?php if (isset($_GET['error']) && $_GET['error'] === 'ja_candidatado'): ?>
    <div class="container">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            Você já se candidatou a esta vaga anteriormente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<div class="cards-container">
    <?php if (!empty($vagas)): ?>
        <?php foreach ($vagas as $vaga): ?>
            <article class='card'>
                <h3><?= htmlspecialchars($vaga['titulo'] ?? '') ?></h3>
                <ul class='details'>
                    <li><strong>Empresa:</strong> <?= htmlspecialchars($vaga['empresa_nome'] ?? 'Não informado') ?></li>
                    <li><strong>Salário:</strong> <?= $this->formatMoney($vaga['salario'] ?? null) ?></li>
                    <li><strong>Localização:</strong> <?= htmlspecialchars($vaga['localizacao'] ?? 'Não informado') ?></li>
                    <li><strong>Contrato:</strong> <?= htmlspecialchars($vaga['tipo_contrato'] ?? 'Não informado') ?></li>
                    <li><strong>Modalidade:</strong> <?= htmlspecialchars($vaga['modalidade'] ?? 'Não informado') ?></li>
                    <?php if (!empty($vaga['created_at'])): ?>
                        <li><strong>Publicada em:</strong> <?= date('d/m/Y', strtotime($vaga['created_at'])) ?></li>
                    <?php endif; ?>
                </ul>
                <a href='/vagas/<?= $vaga['id'] ?? '' ?>' class='btn btn-primary'>Ver mais</a>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-results">
            <?php if (!empty($busca)): ?>
                <p>Nenhuma vaga encontrada para a busca: <strong>"<?= htmlspecialchars($busca) ?>"</strong>.</p>
                <p><a href="/vagas" class="btn btn-primary">Ver todas as vagas</a></p>
            <?php else: ?>
                <p>Nenhuma vaga cadastrada no sistema no momento.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div> 