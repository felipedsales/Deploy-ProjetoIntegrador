<?php
$title = 'Empresas - Ferraz Conecta';
?>

<div class="container fc-directory">
    <header>
        <h1>Empresas parceiras</h1>
        <p>Conheça as empresas que publicam oportunidades no Ferraz Conecta.</p>
        <form action="/empresas" method="get" class="fc-directory-search">
            <label for="busca-empresa" class="visually-hidden">Nome da empresa ou palavra-chave</label>
            <input id="busca-empresa" type="search" name="busca" class="form-control" placeholder="Nome da empresa ou palavra-chave" value="<?= htmlspecialchars($busca ?? '') ?>">
            <button type="submit" class="btn btn-primary">Buscar empresas</button>
        </form>
    </header>
<div class="cards-container">
    <?php if (!empty($empresas)): ?>
        <?php foreach ($empresas as $empresa): ?>
            <div class="empresa-card">
                <div class="empresa-header">
                    <h3><?= htmlspecialchars($empresa['nome'] ?? '') ?></h3>
                </div>

                <ul class="empresa-info">
                    <li><strong>Email:</strong> <?= htmlspecialchars($empresa['email'] ?? '') ?></li>
                    <li><strong>Telefone:</strong> <?= htmlspecialchars($empresa['telefone'] ?? 'Não informado') ?></li>
                    <li><strong>CNPJ:</strong> <?= htmlspecialchars($empresa['cnpj'] ?? 'Não informado') ?></li>
                    <li><strong>Endereço:</strong> <?= htmlspecialchars($empresa['endereco'] ?? 'Não informado') ?></li>
                </ul>

                <?php if (!empty($empresa['descricao'])): ?>
                    <div class="empresa-descricao">
                        <?= htmlspecialchars(substr($empresa['descricao'], 0, 150)) ?><?= strlen($empresa['descricao']) > 150 ? '...' : '' ?>
                    </div>
                <?php endif; ?>

                <div class="mt-3">
                    <a href="/empresas/<?= $empresa['id'] ?? '' ?>" class="btn btn-primary">
                        <i class="fas fa-eye"></i> Ver Detalhes
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-results">
            <?php if (!empty($busca)): ?>
                <p>Nenhuma empresa encontrada para a busca: <strong>"<?= htmlspecialchars($busca) ?>"</strong>.</p>
                <p><a href="/empresas" class="btn btn-primary">Ver todas as empresas</a></p>
            <?php else: ?>
                <p>Nenhuma empresa cadastrada no sistema no momento.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="text-center mt-4">
    <a href="/" class="btn btn-outline-primary">
        <i class="fas fa-home"></i> Página Inicial
    </a>
    <a href="/vagas" class="btn btn-outline-secondary">
        <i class="fas fa-briefcase"></i> Ver Vagas
    </a>
</div>
</div>
