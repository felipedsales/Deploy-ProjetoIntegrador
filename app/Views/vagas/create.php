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

                    <form method="POST" action="/vagas/criar">
                        <div class="mb-3">
                            <label for="titulo" class="form-label required">Título da Vaga</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label required">Descrição Completa</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="6" required
                                placeholder="Descreva detalhadamente a vaga, responsabilidades..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="requisitos" class="form-label">Requisitos</label>
                            <textarea class="form-control" id="requisitos" name="requisitos" rows="4"
                                placeholder="Requisitos desejados para a vaga..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="beneficios" class="form-label">Benefícios</label>
                            <textarea class="form-control" id="beneficios" name="beneficios" rows="4"
                                placeholder="Benefícios oferecidos..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="salario" class="form-label required">Salário (R$)</label>
                                <input type="text" class="form-control" id="salario" name="salario" required>
                                <input type="hidden" name="salario_numerico" id="salario_numerico">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="localizacao" class="form-label required">Localização</label>
                                <input type="text" class="form-control" id="localizacao" name="localizacao" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo_contrato" class="form-label required">Tipo de Contrato</label>
                                <select class="form-select" id="tipo_contrato" name="tipo_contrato" required>
                                    <option value="">Selecione...</option>
                                    <option value="CLT">CLT</option>
                                    <option value="PJ">PJ</option>
                                    <option value="Freelance">Freelance</option>
                                    <option value="Estágio">Estágio</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="modalidade" class="form-label required">Modalidade</label>
                                <select class="form-select" id="modalidade" name="modalidade" required>
                                    <option value="">Selecione...</option>
                                    <option value="Presencial">Presencial</option>
                                    <option value="Remoto">Remoto</option>
                                    <option value="Híbrido">Híbrido</option>
                                </select>
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
