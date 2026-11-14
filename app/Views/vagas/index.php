<?php
$opcoes = [
    'tipo_contrato' => ['Tipo de contrato', array_combine($tiposContrato, $tiposContrato)],
    'modalidade' => ['Modalidade', array_combine($modalidades, $modalidades)],
    'categoria_id' => ['Área', array_column($categorias, 'nome', 'id')],
    'nivel_experiencia' => ['Experiência', array_combine($niveisExperiencia, $niveisExperiencia)],
];
$avisos = [
    'vaga_indisponivel' => 'Esta vaga não está mais disponível.',
    'ja_candidatado' => 'Você já se candidatou a esta vaga anteriormente.',
];
$erro = $_GET['error'] ?? '';
$mostrarData = true;
?>
<div class="fc-page">
    <h1>Vagas abertas</h1>
    <?php if (is_string($erro) && isset($avisos[$erro])): ?>
        <div class="alert alert-warning alert-dismissible" role="alert"><?= $avisos[$erro] ?>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
    <div class="fc-board">
        <form id="pesquisa" class="fc-search" action="/vagas" method="get">
            <label class="visually-hidden" for="busca">Cargo ou palavra-chave</label>
            <input id="busca" type="search" name="busca" placeholder="Digite o cargo ou palavra-chave" value="<?= htmlspecialchars($busca ?? '') ?>">
            <button>Buscar vagas</button>
        </form>
        <details class="fc-filters" open>
            <summary>Filtros</summary>
            <label for="localizacao">Localidade</label>
            <input id="localizacao" form="pesquisa" name="localizacao" value="<?= htmlspecialchars($filtros['localizacao'] ?? '') ?>" placeholder="Cidade ou região">
            <?php foreach ($opcoes as $campo => [$label, $valores]): ?>
                <label for="<?= $campo ?>"><?= $label ?></label>
                <select id="<?= $campo ?>" name="<?= $campo ?>" form="pesquisa">
                    <option value="">Todas as opções</option>
                    <?php foreach ($valores as $valor => $nome): ?>
                        <option value="<?= htmlspecialchars((string) $valor) ?>" <?= (string) ($filtros[$campo] ?? '') === (string) $valor ? 'selected' : '' ?>><?= htmlspecialchars($nome) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endforeach; ?>
            <div class="fc-filter-actions"><button form="pesquisa">Aplicar filtros</button><a href="/vagas">Limpar</a></div>
        </details>
        <div class="fc-results">
            <p><?= count($vagas) ?> <?= count($vagas) === 1 ? 'vaga encontrada' : 'vagas encontradas' ?></p>
            <?php foreach ($vagas as $vaga) include __DIR__ . '/../partials/vaga-resumo.php'; ?>
            <?php if (!$vagas): ?>
                <?php if (array_filter($filtros)): ?><p>Tente outro cargo ou <a href="/vagas">limpe os filtros</a>.</p>
                <?php else: ?><p>Nenhuma vaga disponível no momento.</p><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    const telaEstreita = window.matchMedia('(max-width: 720px)');
    const filtrosVagas = document.querySelector('.fc-filters');
    function ajustarFiltros() { filtrosVagas.open = !telaEstreita.matches; }
    ajustarFiltros();
    telaEstreita.addEventListener('change', ajustarFiltros);
</script>
