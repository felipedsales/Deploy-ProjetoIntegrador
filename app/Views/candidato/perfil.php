<?php
$title = 'Meu Perfil - Ferraz Conecta';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        <i class="fas fa-user text-primary"></i> Meu Perfil
                    </h2>

                    <?php
                    $mensagens = [
                        'perfil_atualizado' => 'Perfil atualizado com sucesso!',
                        'curriculo_enviado' => 'Currículo enviado com sucesso!',
                        'curriculo_removido' => 'Currículo removido.',
                        'carta_enviada' => 'Carta de apresentação enviada com sucesso!',
                        'carta_removida' => 'Carta de apresentação removida.',
                    ];
                    ?>
                    <?php if (isset($mensagens[$_GET['status'] ?? ''])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?= $mensagens[$_GET['status']] ?>
                        </div>
                    <?php endif; ?>

                    <?php if (($_GET['error'] ?? '') === 'curriculo_obrigatorio'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Para se candidatar a uma vaga, envie primeiro o seu currículo.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($erros)): ?>
                        <div class="alert alert-danger">
                            <strong>Não foi possível salvar o perfil:</strong>
                            <ul class="mb-0">
                                <?php foreach ($erros as $mensagem): ?>
                                    <li><?= htmlspecialchars($mensagem) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/perfil/atualizar">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label required">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                    value="<?= htmlspecialchars($candidato['nome']) ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" 
                                    value="<?= htmlspecialchars($candidato['email']) ?>" readonly>
                                <small class="text-muted">O email não pode ser alterado</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label required">Telefone</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone" 
                                    value="<?= htmlspecialchars($candidato['telefone'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cpf" class="form-label">CPF</label>
                                <input type="text" class="form-control" id="cpf" 
                                    value="<?= htmlspecialchars($candidato['cpf'] ?? '') ?>" readonly>
                                <small class="text-muted">O CPF não pode ser alterado</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" id="data_nascimento" 
                                value="<?= htmlspecialchars($candidato['data_nascimento'] ?? '') ?>" readonly>
                            <small class="text-muted">A data de nascimento não pode ser alterada</small>
                        </div>

                        <div class="mb-3">
                            <label for="endereco" class="form-label required">Endereço</label>
                            <textarea class="form-control" id="endereco" name="endereco" rows="3" required><?= htmlspecialchars($candidato['endereco'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha (opcional)</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" 
                                placeholder="Deixe em branco para manter a senha atual">
                            <small class="text-muted">Preencha apenas se desejar alterar sua senha. Mínimo 6 caracteres, uma letra e um número</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Atualizar Perfil
                            </button>
                            <a href="/" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4" id="curriculo">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-alt text-primary"></i> Currículo</h5>

                    <?php if (!empty($erroCurriculo)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erroCurriculo) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($curriculo)): ?>
                        <p class="mb-2">
                            <span class="badge bg-success">Enviado</span>
                            <?= htmlspecialchars($curriculo['nome_arquivo']) ?><br>
                            <small class="text-muted">em <?= date('d/m/Y \\à\\s H:i', strtotime($curriculo['data_envio'])) ?></small>
                        </p>
                        <div class="d-flex gap-2 mb-3">
                            <a href="/perfil/curriculo" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download"></i> Baixar
                            </a>
                            <form method="POST" action="/perfil/curriculo/remover"
                                onsubmit="return confirm('Remover o currículo? Sem ele você não consegue se candidatar.')">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-trash"></i> Remover
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Nenhum currículo enviado. Ele é necessário para se candidatar.</p>
                    <?php endif; ?>

                    <form method="POST" action="/perfil/curriculo" enctype="multipart/form-data">
                        <div class="mb-2">
                            <label for="curriculo_arquivo" class="form-label">
                                <?= !empty($curriculo) ? 'Substituir currículo' : 'Enviar currículo' ?>
                            </label>
                            <input type="file" class="form-control" id="curriculo_arquivo" name="curriculo" accept=".pdf,.doc,.docx" required>
                            <small class="text-muted">PDF, DOC ou DOCX de até 2 MB</small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Enviar</button>
                    </form>
                </div>
            </div>

            <div class="card shadow mb-4" id="carta">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-envelope-open-text text-primary"></i> Carta de apresentação</h5>
                    <p class="text-muted small">Opcional. Esta é a carta padrão, usada nas candidaturas em que você não enviar outra.</p>

                    <?php if (!empty($erroCarta)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erroCarta) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($carta)): ?>
                        <p class="mb-2">
                            <span class="badge bg-success">Enviada</span>
                            <?= htmlspecialchars($carta['nome_arquivo']) ?>
                        </p>
                        <div class="d-flex gap-2 mb-3">
                            <a href="/perfil/carta" class="btn btn-outline-primary btn-sm"><i class="fas fa-download"></i> Baixar</a>
                            <form method="POST" action="/perfil/carta/remover" onsubmit="return confirm('Remover a carta de apresentação?')">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Remover</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/perfil/carta" enctype="multipart/form-data">
                        <div class="mb-2">
                            <label for="carta_arquivo" class="form-label"><?= !empty($carta) ? 'Substituir carta' : 'Enviar carta' ?></label>
                            <input type="file" class="form-control" id="carta_arquivo" name="carta" accept=".pdf,.doc,.docx" required>
                            <small class="text-muted">PDF, DOC ou DOCX de até 2 MB</small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Enviar</button>
                    </form>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">Ações Rápidas</h5>
                    <div class="d-grid gap-2">
                        <a href="/minhas-candidaturas" class="btn btn-outline-primary">
                            <i class="fas fa-briefcase"></i> Minhas Candidaturas
                        </a>
                        <a href="/vagas" class="btn btn-outline-success">
                            <i class="fas fa-search"></i> Procurar Vagas
                        </a>
                        <a href="/logout" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title">Informações da Conta</h5>
                    <ul class="list-unstyled">
                        <li><strong>Membro desde:</strong> <?= date('d/m/Y', strtotime($candidato['created_at'])) ?></li>
                        <li><strong>Status:</strong> <span class="badge bg-success">Ativo</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div> 