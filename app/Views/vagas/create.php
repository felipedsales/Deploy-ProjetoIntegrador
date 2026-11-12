<?php
$title = 'Criar Nova Vaga - Ferraz Conecta';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        <i class="fas fa-plus-circle text-primary"></i> Criar Nova Vaga
                    </h2>

                    <?php if (!empty($erros)): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong>Não foi possível salvar a vaga:</strong>
                            <ul class="mb-0">
                                <?php foreach ($erros as $mensagem): ?>
                                    <li><?= htmlspecialchars($mensagem) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/vagas/criar">
                        <div class="mb-3">
                            <label for="titulo" class="form-label required">Título da Vaga</label>
                            <input type="text" class="form-control" id="titulo" name="titulo"
                                value="<?= htmlspecialchars($vaga['titulo'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label required">Descrição Completa</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="6" required
                                placeholder="Descreva detalhadamente a vaga, responsabilidades..."><?= htmlspecialchars($vaga['descricao'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="requisitos" class="form-label required">Requisitos</label>
                            <textarea class="form-control" id="requisitos" name="requisitos" rows="4" required
                                placeholder="Requisitos desejados para a vaga..."><?= htmlspecialchars($vaga['requisitos'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="beneficios" class="form-label">Benefícios</label>
                            <textarea class="form-control" id="beneficios" name="beneficios" rows="4"
                                placeholder="Benefícios oferecidos..."><?= htmlspecialchars($vaga['beneficios'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="salario" class="form-label required">Salário (R$)</label>
                                <input type="text" class="form-control" id="salario" name="salario"
                                    value="<?= htmlspecialchars((string) ($vaga['salario'] ?? '')) ?>" required>
                                <input type="hidden" name="salario_numerico" id="salario_numerico" value="<?= htmlspecialchars((string) ($vaga['salario'] ?? '')) ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="localizacao" class="form-label required">Localização</label>
                                <input type="text" class="form-control" id="localizacao" name="localizacao"
                                    value="<?= htmlspecialchars($vaga['localizacao'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tipo_contrato" class="form-label required">Tipo de Contrato</label>
                                <select class="form-select" id="tipo_contrato" name="tipo_contrato" required>
                                    <option value="">Selecione...</option>
                                    <option value="CLT" <?= ($vaga['tipo_contrato'] ?? '') === 'CLT' ? 'selected' : '' ?>>CLT</option>
                                    <option value="PJ" <?= ($vaga['tipo_contrato'] ?? '') === 'PJ' ? 'selected' : '' ?>>PJ</option>
                                    <option value="Freelance" <?= ($vaga['tipo_contrato'] ?? '') === 'Freelance' ? 'selected' : '' ?>>Freelance</option>
                                    <option value="Estágio" <?= ($vaga['tipo_contrato'] ?? '') === 'Estágio' ? 'selected' : '' ?>>Estágio</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="modalidade" class="form-label required">Modalidade</label>
                                <select class="form-select" id="modalidade" name="modalidade" required>
                                    <option value="">Selecione...</option>
                                    <option value="Presencial" <?= ($vaga['modalidade'] ?? '') === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                                    <option value="Remoto" <?= ($vaga['modalidade'] ?? '') === 'Remoto' ? 'selected' : '' ?>>Remoto</option>
                                    <option value="Híbrido" <?= ($vaga['modalidade'] ?? '') === 'Híbrido' ? 'selected' : '' ?>>Híbrido</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="data_expiracao" class="form-label required">Receber candidaturas até</label>
                                <input type="date" class="form-control<?= isset($erros['data_expiracao']) ? ' is-invalid' : '' ?>"
                                    id="data_expiracao" name="data_expiracao"
                                    value="<?= htmlspecialchars($vaga['data_expiracao'] ?? '') ?>"
                                    min="<?= htmlspecialchars($dataMinima ?? '') ?>" required>
                                <div class="form-text">A vaga fica aberta por no mínimo 7 dias.</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Criar Vaga
                            </button>
                            <a href="/painel-empresa" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
