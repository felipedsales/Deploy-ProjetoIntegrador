<?php
// RF05 — troca da senha a partir do link recebido por e-mail.
$title = 'Redefinir senha - Ferraz Conecta';
$erros = $erros ?? [];
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Redefinir senha</h2>

                    <?php if (!empty($linkInvalido) || isset($erros['link'])): ?>
                        <div class="alert alert-danger">Este link expirou ou já foi usado.</div>
                        <a href="/recuperar-senha" class="btn btn-primary w-100">Pedir um novo link</a>
                    <?php else: ?>
                        <form method="POST" action="/redefinir-senha/<?= htmlspecialchars($token) ?>">
                            <div class="mb-3">
                                <label for="senha" class="form-label required">Nova senha</label>
                                <input type="password" class="form-control <?= isset($erros['senha']) ? 'is-invalid' : '' ?>"
                                       id="senha" name="senha" required>
                                <?php if (isset($erros['senha'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($erros['senha']) ?></div>
                                <?php else: ?>
                                    <div class="form-text">Mínimo de 6 caracteres, com pelo menos uma letra e um número.</div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="confirmacao" class="form-label required">Confirme a nova senha</label>
                                <input type="password" class="form-control <?= isset($erros['confirmacao']) ? 'is-invalid' : '' ?>"
                                       id="confirmacao" name="confirmacao" required>
                                <?php if (isset($erros['confirmacao'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($erros['confirmacao']) ?></div>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Salvar nova senha</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
