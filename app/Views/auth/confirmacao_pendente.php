<?php
// RN22 — conta aguardando confirmação de e-mail.
$title = 'Confirme seu e-mail - Ferraz Conecta';
$tipo = $tipo ?? 'candidato';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <?php if (!empty($linkInvalido)): ?>
                        <h2 class="text-center mb-4">Link inválido</h2>
                        <div class="alert alert-danger">
                            Este link de confirmação expirou ou já foi usado.
                        </div>
                        <p>Se a sua conta ainda não foi confirmada, peça um novo link abaixo.</p>
                    <?php else: ?>
                        <h2 class="text-center mb-4">Confirme seu e-mail</h2>

                        <?php if (!empty($reenviado)): ?>
                            <div class="alert alert-success">
                                Se houver uma conta aguardando confirmação com este e-mail, um novo link foi enviado.
                            </div>
                        <?php elseif (($envio ?? '') === 'falha_envio'): ?>
                            <div class="alert alert-warning">
                                Sua conta foi criada, mas não conseguimos enviar o e-mail de confirmação agora.
                                Peça um novo link abaixo em alguns instantes.
                            </div>
                        <?php elseif (!empty($recemCriada)): ?>
                            <div class="alert alert-success">
                                Conta criada. Enviamos um link de confirmação para
                                <strong><?= htmlspecialchars($email ?? '') ?></strong>.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Sua conta ainda não foi ativada. Abra o link que enviamos para
                                <strong><?= htmlspecialchars($email ?? '') ?></strong>.
                            </div>
                        <?php endif; ?>

                        <p>O link vale por 24 horas. Confira também a caixa de spam.</p>
                    <?php endif; ?>

                    <form method="POST" action="/confirmar-email/reenviar">
                        <div class="mb-3">
                            <label for="email" class="form-label required">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($email ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="tipo" class="form-label required">Tipo de Conta</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="candidato" <?= $tipo === 'candidato' ? 'selected' : '' ?>>Candidato</option>
                                <option value="empresa" <?= $tipo === 'empresa' ? 'selected' : '' ?>>Empresa</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-primary w-100 mb-3">Reenviar link de confirmação</button>
                    </form>

                    <p class="text-center mb-0"><a href="/login">Voltar para o login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
