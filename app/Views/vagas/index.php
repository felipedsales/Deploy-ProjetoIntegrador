<?php
$title = 'Vagas - Ferraz Conecta';
?>

<section class="search-section">
    <div class="container">
        <h1 class="page-title">Nossas Vagas Abertas</h1>
        <p class="page-subtitle">Encontre a oportunidade ideal para você em Ferraz de Vasconcelos.</p>
        
        <form action="/vagas" method="get" class="search-form">
            <div class="input-group">
                <input type="text" name="busca" class="form-control" placeholder="Digite o cargo ou palavra-chave" aria-label="Cargo ou palavra-chave" value="<?= htmlspecialchars($busca ?? '') ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar Vagas
                </button>
            </div>

            <div class="row g-2 mt-2">
                <div class="col-md-4">
                    <input type="text" name="localizacao" class="form-control" placeholder="Localidade" aria-label="Localidade"
                        value="<?= htmlspecialchars($filtros['localizacao'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <select name="tipo_contrato" class="form-select" aria-label="Tipo de contrato">
                        <option value="">Tipo de contrato</option>
                        <?php foreach ($tiposContrato ?? [] as $tipo): ?>
                            <option value="<?= $tipo ?>" <?= ($filtros['tipo_contrato'] ?? '') === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="modalidade" class="form-select" aria-label="Modalidade de trabalho">
                        <option value="">Modalidade</option>
                        <?php foreach ($modalidades ?? [] as $modalidade): ?>
                            <option value="<?= $modalidade ?>" <?= ($filtros['modalidade'] ?? '') === $modalidade ? 'selected' : '' ?>><?= $modalidade ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="/vagas" class="btn btn-outline-secondary w-100">Limpar</a>
                </div>
            </div>
        </form>
    </div>
</section>

<?php if (isset($_GET['error']) && $_GET['error'] === 'vaga_indisponivel'): ?>
    <div class="container">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            Esta vaga não está mais disponível.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

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
            <?php if (array_filter($filtros ?? [])): ?>
                <p>Nenhuma vaga encontrada com os filtros informados.</p>
                <p><a href="/vagas" class="btn btn-primary">Ver todas as vagas</a></p>
            <?php else: ?>
                <p>Nenhuma vaga cadastrada no sistema no momento.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div> 