<?php
// RF25 — sugestão de vagas por regras (RN13).
$title = 'Vagas Recomendadas - Ferraz Conecta';
?>

<div class="container mt-4">
    <h2 class="mb-4">
        <i class="fas fa-list-ol text-primary"></i> Vagas Recomendadas
    </h2>

    <?php if ($vagas === null): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Para receber recomendações, anexe ao perfil um currículo em PDF ou DOCX com texto
            (arquivo .doc ou PDF digitalizado não pode ser lido).
            <a href="/perfil" class="alert-link">Ir para o perfil</a>
        </div>
    <?php else: ?>
        <p class="text-muted">
            A recomendação compara os requisitos de cada vaga aberta com o texto do seu currículo.
            Nenhum dado pessoal entra na conta.
        </p>

        <?php if (!empty($vagas)): ?>
            <div class="cards-container">
                <?php foreach ($vagas as $vaga): ?>
                    <div class="card">
                        <div class="card-body">
                            <h3><?= htmlspecialchars($vaga['titulo'] ?? '') ?></h3>

                            <div class="mb-3">
                                <strong>Aderência: <?= (int) $vaga['aderencia']['pontuacao'] ?>%</strong>
                                <div class="progress" style="height: 8px;" role="progressbar"
                                     aria-label="Aderência" aria-valuenow="<?= (int) $vaga['aderencia']['pontuacao'] ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: <?= (int) $vaga['aderencia']['pontuacao'] ?>%"></div>
                                </div>
                                <small class="d-block mt-1"><strong>No seu currículo:</strong> <?= htmlspecialchars(implode(', ', $vaga['aderencia']['atendidos'])) ?></small>
                                <?php if ($vaga['aderencia']['nao_encontrados']): ?>
                                    <small class="d-block text-muted"><strong>Não encontrados:</strong> <?= htmlspecialchars(implode(', ', $vaga['aderencia']['nao_encontrados'])) ?></small>
                                <?php endif; ?>
                            </div>

                            <ul class="details">
                                <li><strong>Empresa:</strong> <?= htmlspecialchars($vaga['empresa_nome'] ?? 'Não informado') ?></li>
                                <li><strong>Salário:</strong> <?= $this->formatMoney($vaga['salario'] ?? null) ?></li>
                                <li><strong>Localização:</strong> <?= htmlspecialchars($vaga['localizacao'] ?? 'Não informado') ?></li>
                                <li><strong>Tipo de Contrato:</strong> <?= htmlspecialchars($vaga['tipo_contrato'] ?? 'Não informado') ?></li>
                                <li><strong>Modalidade:</strong> <?= htmlspecialchars($vaga['modalidade'] ?? 'Não informado') ?></li>
                                <li><strong>Candidaturas até:</strong> <?= date('d/m/Y', strtotime($vaga['data_expiracao'])) ?></li>
                            </ul>

                            <a href="/vagas/<?= $vaga['id'] ?>" class="btn btn-outline-primary mt-3">
                                <i class="fas fa-eye"></i> Ver Vaga
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary">
                Nenhuma vaga aberta tem requisitos que apareçam no seu currículo no momento.
                <a href="/vagas" class="alert-link">Ver todas as vagas</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
