<article class="fc-card">
    <h3><a href="/vagas/<?= (int) $vaga['id'] ?>"><?= htmlspecialchars($vaga['titulo']) ?></a></h3>
    <p><?= htmlspecialchars($vaga['empresa_nome'] ?? 'Empresa não informada') ?></p>
    <ul class="fc-meta">
        <li><span class="visually-hidden">Salário: </span><?= $this->formatMoney($vaga['salario'] ?? null) ?></li>
        <?php foreach (['localizacao' => 'Localização', 'tipo_contrato' => 'Contrato', 'modalidade' => 'Modalidade'] as $campo => $rotulo): ?>
            <?php if (!empty($vaga[$campo])): ?>
                <li><span class="visually-hidden"><?= $rotulo ?>: </span><?= htmlspecialchars($vaga[$campo]) ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
    <?php if (!empty($mostrarData) && !empty($vaga['created_at'])): ?>
        <small>Publicada em <?= date('d/m/Y', strtotime($vaga['created_at'])) ?></small>
    <?php endif; ?>
</article>
