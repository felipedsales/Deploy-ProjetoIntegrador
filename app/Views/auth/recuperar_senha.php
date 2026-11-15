<?php
// RF05 — pedido do link de redefinição de senha.
$title = 'Recuperar senha - Ferraz Conecta';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Recuperar senha</h2>

                    <?php if (empty($disponivel)): ?>
                        <div class="alert alert-warning">
                            A recuperação de senha por e-mail está indisponível no momento.
                            Entre em contato com a Prefeitura.
                        </div>
                    <?php elseif (!empty($enviado)): ?>
                        <div class="alert alert-success">
                            Se o e-mail estiver cadastrado, enviamos um link para redefinir a senha.
                            O link vale por 15 minutos.
                        </div>
                    <?php else: ?>
                        <p>Informe o e-mail da conta. Enviaremos um link válido por 15 minutos.</p>

                        <form method="POST" action="/recuperar-senha">
                            <div class="mb-3">
                                <label for="email" class="form-label required">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipo" class="form-label required">Tipo de Conta</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="candidato">Candidato</option>
                                    <option value="empresa">Empresa</option>
                                    <option value="administrador">Administrador</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">Enviar link</button>
                        </form>
                    <?php endif; ?>

                    <p class="text-center mb-0"><a href="/login">Voltar para o login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
