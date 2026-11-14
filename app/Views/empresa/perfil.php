<?php
$title = 'Perfil da Empresa - Ferraz Conecta';
$mensagens = [
    'perfil_atualizado' => 'Perfil atualizado com sucesso!',
    'logo_enviado' => 'Logo atualizado com sucesso!',
];
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        <i class="fas fa-building text-primary"></i> Perfil da Empresa
                    </h2>

                    <?php if (isset($mensagens[$_GET['status'] ?? ''])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?= $mensagens[$_GET['status']] ?>
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

                    <form method="POST" action="/empresa/perfil">
                        <div class="mb-3">
                            <label for="nome" class="form-label required">Razão Social</label>
                            <input type="text" class="form-control" id="nome" name="nome"
                                value="<?= htmlspecialchars($empresa['nome'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="responsavel" class="form-label required">Responsável pela conta</label>
                            <input type="text" class="form-control" id="responsavel" name="responsavel"
                                value="<?= htmlspecialchars($empresa['responsavel'] ?? '') ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email"
                                    value="<?= htmlspecialchars($empresa['email'] ?? '') ?>" readonly>
                                <small class="text-muted">O email não pode ser alterado</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cnpj" class="form-label">CNPJ</label>
                                <input type="text" class="form-control" id="cnpj"
                                    value="<?= htmlspecialchars($empresa['cnpj'] ?? '') ?>" readonly>
                                <small class="text-muted">O CNPJ não pode ser alterado</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label required">Telefone</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone"
                                    value="<?= htmlspecialchars($empresa['telefone'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="setor" class="form-label">Setor de atuação</label>
                                <input type="text" class="form-control" id="setor" name="setor" maxlength="100"
                                    value="<?= htmlspecialchars($empresa['setor'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="website" class="form-label">Site</label>
                            <input type="url" class="form-control" id="website" name="website"
                                value="<?= htmlspecialchars($empresa['website'] ?? '') ?>" placeholder="https://">
                        </div>

                        <?php $perfilEndereco = $empresa; include __DIR__ . '/../partials/endereco.php'; ?>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição institucional</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="5"><?= htmlspecialchars($empresa['descricao'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha (opcional)</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha"
                                placeholder="Deixe em branco para manter a senha atual">
                            <small class="text-muted">Mínimo 6 caracteres, uma letra e um número</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Perfil
                        </button>
                        <a href="/painel-empresa" class="btn btn-outline-secondary">Voltar ao Painel</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-image text-primary"></i> Logo</h5>

                    <?php if (!empty($erroLogo)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erroLogo) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($logo)): ?>
                        <img src="/empresas/<?= $empresa['id'] ?>/logo?v=<?= strtotime($logo['data_envio']) ?>"
                            alt="Logo de <?= htmlspecialchars($empresa['nome'] ?? '') ?>" class="img-fluid mb-3" style="max-height: 160px;">
                    <?php else: ?>
                        <p class="text-muted">Nenhum logo enviado.</p>
                    <?php endif; ?>

                    <form method="POST" action="/empresa/perfil/logo" enctype="multipart/form-data">
                        <div class="mb-2">
                            <label for="logo" class="form-label">Enviar novo logo</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept=".png,.jpg,.jpeg,.webp" required>
                            <small class="text-muted">PNG, JPG ou WEBP de até 1 MB</small>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm">Enviar logo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
