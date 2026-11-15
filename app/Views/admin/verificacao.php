<?php
// RNF09 / RF03 — segundo fator do administrador.
$title = 'Verificação em duas etapas - Ferraz Conecta';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Código de acesso</h2>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif (($envio ?? '') === 'enviado'): ?>
                        <div class="alert alert-success">Enviamos um novo código.</div>
                    <?php elseif (($envio ?? '') === 'aguarde'): ?>
                        <div class="alert alert-warning">Aguarde um minuto antes de pedir outro código.</div>
                    <?php elseif (($envio ?? '') === 'falha_envio'): ?>
                        <div class="alert alert-danger">Não foi possível enviar o código. Tente novamente em instantes.</div>
                    <?php endif; ?>

                    <p>Enviamos um código de 6 dígitos para o seu e-mail. Ele vale por 10 minutos.</p>

                    <form method="POST" action="/admin/verificacao">
                        <div class="mb-3">
                            <label for="codigo" class="form-label required">Código</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" inputmode="numeric"
                                   autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">Entrar</button>
                    </form>

                    <form method="POST" action="/admin/verificacao">
                        <input type="hidden" name="acao" value="reenviar">
                        <button type="submit" class="btn btn-link w-100">Reenviar código</button>
                    </form>

                    <p class="text-center mb-0"><a href="/admin/login">Voltar</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
