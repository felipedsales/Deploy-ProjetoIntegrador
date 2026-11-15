<?php
$title = 'Suporte - Ferraz Conecta';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-life-ring text-primary"></i> Suporte</h2>

    <?php if (($_GET['status'] ?? '') === 'aberto'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Pedido registrado. A resposta chega em até <?= (int) $prazoDias ?> dias úteis e aparece aqui e nas suas notificações.
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Novo pedido</h5>

                    <?php if (!empty($erros)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($erros as $mensagem): ?>
                                    <li><?= htmlspecialchars($mensagem) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/suporte">
                        <div class="mb-3">
                            <label for="assunto" class="form-label required">Assunto</label>
                            <input type="text" class="form-control" id="assunto" name="assunto" maxlength="150"
                                value="<?= htmlspecialchars($enviado['assunto'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="mensagem" class="form-label required">O que aconteceu?</label>
                            <textarea class="form-control" id="mensagem" name="mensagem" rows="5" maxlength="5000" required><?= htmlspecialchars($enviado['mensagem'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar pedido</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <h5>Meus pedidos</h5>
            <?php if (empty($tickets)): ?>
                <p class="text-muted">Você ainda não abriu nenhum pedido.</p>
            <?php endif; ?>

            <?php foreach ($tickets as $ticket): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <strong>#<?= $ticket['id'] ?> — <?= htmlspecialchars($ticket['assunto']) ?></strong>
                            <span class="badge bg-<?= $ticket['status'] === 'Respondido' ? 'success' : 'warning' ?>"><?= $ticket['status'] ?></span>
                        </div>
                        <p class="small text-muted mb-2">
                            Aberto em <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                            <?php if ($ticket['status'] === 'Aberto'): ?>
                                · resposta prevista até <?= date('d/m/Y', strtotime($ticket['prazo'])) ?>
                            <?php endif; ?>
                        </p>
                        <p class="mb-2"><?= nl2br(htmlspecialchars($ticket['mensagem'])) ?></p>
                        <?php if ($ticket['status'] === 'Respondido'): ?>
                            <div class="border-start border-success border-3 ps-3">
                                <div class="small text-muted">Resposta do suporte em <?= date('d/m/Y H:i', strtotime($ticket['respondido_em'])) ?></div>
                                <?= nl2br(htmlspecialchars($ticket['resposta'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
