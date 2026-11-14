<div class="fc-page">
    <?php if (($_GET['status'] ?? '') === 'candidatura_sucesso'): ?>
        <p class="alert alert-success" role="status">Sua candidatura foi realizada com sucesso. Boa sorte!</p>
    <?php endif; ?>
    <div class="fc-hero">
        <div class="fc-intro">
            <h1>Encontre sua próxima vaga</h1>
            <p>Oportunidades de trabalho em Ferraz de Vasconcelos e região.</p>
            <form action="/vagas" method="get" class="fc-search">
                <label class="visually-hidden" for="busca">Cargo ou palavra-chave</label>
                <input id="busca" type="search" name="busca" placeholder="Digite o cargo ou palavra-chave">
                <button>Buscar vagas</button>
            </form>
        </div>
        <figure>
            <img src="/img/estacao-ferraz.webp" alt="Trem na plataforma da estação de Ferraz de Vasconcelos" width="1050" height="700">
            <figcaption>Estação Ferraz de Vasconcelos · <a href="https://commons.wikimedia.org/wiki/File:Esta%C3%A7%C3%A3o_Ferraz_de_Vasconcelos.jpg">Governo do Estado de São Paulo</a> · <a href="https://creativecommons.org/licenses/by/2.0/">CC BY 2.0</a> (recorte)</figcaption>
        </figure>
    </div>
    <section aria-labelledby="destaque">
        <div class="fc-heading"><h2 id="destaque">Vagas em destaque</h2><a href="/vagas">Ver todas as vagas →</a></div>
        <div class="fc-featured">
            <?php foreach ($vagasDestaque as $vaga) include __DIR__ . '/../partials/vaga-resumo.php'; ?>
        </div>
        <?php if (!$vagasDestaque): ?><p>Nenhuma vaga disponível no momento.</p><?php endif; ?>
    </section>
    <dl class="fc-stats" id="estatisticas">
        <?php foreach (['Candidatos cadastrados' => $totalCandidatos, 'Empresas parceiras' => $totalEmpresas, 'Vagas disponíveis' => $totalVagas] as $label => $total): ?>
            <div><dt><?= $label ?></dt><dd><?= (int) $total ?></dd></div>
        <?php endforeach; ?>
    </dl>
</div>
