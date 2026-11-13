<?php
$title = 'Editar Vaga - Ferraz Conecta';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        <i class="fas fa-edit text-primary"></i> Editar Vaga
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

                    <form method="POST" action="/vagas/<?= $vaga['id'] ?? '' ?>/editar">
                        <div class="mb-3">
                            <label for="titulo" class="form-label required">Título da Vaga</label>
                            <input type="text" class="form-control" id="titulo" name="titulo"
                                value="<?= htmlspecialchars($vaga['titulo'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label required">Descrição Completa</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="6" required><?= htmlspecialchars($vaga['descricao'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="requisitos" class="form-label required">Requisitos</label>
                            <textarea class="form-control" id="requisitos" name="requisitos" rows="4" required><?= htmlspecialchars($vaga['requisitos'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="beneficios" class="form-label">Benefícios</label>
                            <textarea class="form-control" id="beneficios" name="beneficios" rows="4"><?= htmlspecialchars($vaga['beneficios'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="salario" class="form-label required">Salário (R$)</label>
                                <input type="text" class="form-control" id="salario" name="salario"
                                    value="<?= htmlspecialchars($vaga['salario'] ?? '') ?>" required>
                                <input type="hidden" name="salario_numerico" id="salario_numerico" value="<?= htmlspecialchars($vaga['salario'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="localizacao" class="form-label required">Localização</label>
                                <input type="text" class="form-control" id="localizacao" name="localizacao"
                                    value="<?= htmlspecialchars($vaga['localizacao'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="categoria_id" class="form-label">Categoria (área)</label>
                                <select class="form-select" id="categoria_id" name="categoria_id">
                                    <option value="">Não informada</option>
                                    <?php foreach ($categorias ?? [] as $categoria): ?>
                                        <option value="<?= $categoria['id'] ?>" <?= (string) ($vaga['categoria_id'] ?? '') === (string) $categoria['id'] ? 'selected' : '' ?>><?= htmlspecialchars($categoria['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nivel_experiencia" class="form-label">Nível de experiência</label>
                                <select class="form-select" id="nivel_experiencia" name="nivel_experiencia">
                                    <option value="">Não informado</option>
                                    <?php foreach ($niveisExperiencia ?? [] as $nivel): ?>
                                        <option value="<?= $nivel ?>" <?= ($vaga['nivel_experiencia'] ?? '') === $nivel ? 'selected' : '' ?>><?= $nivel ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tipo_contrato" class="form-label required">Tipo de Contrato</label>
                                <select class="form-select" id="tipo_contrato" name="tipo_contrato" required>
                                    <option value="CLT" <?= ($vaga['tipo_contrato'] ?? '') === 'CLT' ? 'selected' : '' ?>>CLT</option>
                                    <option value="PJ" <?= ($vaga['tipo_contrato'] ?? '') === 'PJ' ? 'selected' : '' ?>>PJ</option>
                                    <option value="Freelance" <?= ($vaga['tipo_contrato'] ?? '') === 'Freelance' ? 'selected' : '' ?>>Freelance</option>
                                    <option value="Estágio" <?= ($vaga['tipo_contrato'] ?? '') === 'Estágio' ? 'selected' : '' ?>>Estágio</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="modalidade" class="form-label required">Modalidade</label>
                                <select class="form-select" id="modalidade" name="modalidade" required>
                                    <option value="Presencial" <?= ($vaga['modalidade'] ?? '') === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                                    <option value="Remoto" <?= ($vaga['modalidade'] ?? '') === 'Remoto' ? 'selected' : '' ?>>Remoto</option>
                                    <option value="Híbrido" <?= ($vaga['modalidade'] ?? '') === 'Híbrido' ? 'selected' : '' ?>>Híbrido</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label required">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Ativa" <?= ($vaga['status'] ?? '') === 'Ativa' ? 'selected' : '' ?>>Ativa</option>
                                    <option value="Pausada" <?= ($vaga['status'] ?? '') === 'Pausada' ? 'selected' : '' ?>>Pausada</option>
                                    <option value="Inativa" <?= ($vaga['status'] ?? '') === 'Inativa' ? 'selected' : '' ?>>Inativa</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
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
                                <i class="fas fa-save"></i> Atualizar Vaga
                            </button>
                            <a href="/painel-empresa" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>

                    <?php if (!empty($historico)): ?>
                        <hr>
                        <h5><i class="fas fa-history text-secondary"></i> Histórico de alterações</h5>
                        <?php foreach ($historico as $registro): ?>
                            <p class="mb-1"><strong><?= date('d/m/Y \\à\\s H:i', strtotime($registro['created_at'])) ?></strong></p>
                            <ul class="small">
                                <?php foreach ($registro['alteracoes'] as $campo => $mudanca): ?>
                                    <?php
                                    $nomeCampo = \App\Models\Vaga::CAMPOS_HISTORICO[$campo] ?? $campo;
                                    $curto = in_array($campo, ['descricao', 'requisitos', 'beneficios'], true);
                                    $mostrar = function ($valor) use ($campo, $categorias) {
                                        if ($valor === null) {
                                            return '(vazio)';
                                        }
                                        if ($campo === 'categoria_id') {
                                            foreach ($categorias ?? [] as $categoria) {
                                                if ((string) $categoria['id'] === (string) $valor) {
                                                    return $categoria['nome'];
                                                }
                                            }
                                            return 'categoria removida';
                                        }
                                        if ($campo === 'data_expiracao') {
                                            return date('d/m/Y', strtotime($valor));
                                        }
                                        if ($campo === 'salario') {
                                            return 'R$ ' . number_format((float) $valor, 2, ',', '.');
                                        }
                                        return $valor;
                                    };
                                    ?>
                                    <li>
                                        <strong><?= htmlspecialchars($nomeCampo) ?>:</strong>
                                        <?php if ($curto): ?>
                                            texto alterado
                                        <?php else: ?>
                                            <?= htmlspecialchars($mostrar($mudanca['de'])) ?> → <?= htmlspecialchars($mostrar($mudanca['para'])) ?>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
