<?php
$title = 'Administradores - Ferraz Conecta';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-user-shield text-primary"></i> Administradores</h2>

    <?php include __DIR__ . '/_avisos.php'; ?>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr><th>Nome</th><th>Email</th><th>Cadastro</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($administradores as $administrador): ?>
                            <tr>
                                <td><?= htmlspecialchars($administrador['nome']) ?></td>
                                <td><?= htmlspecialchars($administrador['email']) ?></td>
                                <td><?= date('d/m/Y', strtotime($administrador['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Novo administrador</h5>

                    <?php if (!empty($erros)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($erros as $mensagem): ?>
                                    <li><?= htmlspecialchars($mensagem) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/admin/administradores">
                        <div class="mb-3">
                            <label for="nome" class="form-label required">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome"
                                value="<?= htmlspecialchars($dados['nome'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label required">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?= htmlspecialchars($dados['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="senha" class="form-label required">Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha" minlength="8" required>
                            <div class="form-text">No mínimo 8 caracteres.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
