<?php
$title = 'Suporte - Painel Administrativo - Ferraz Conecta';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-life-ring text-primary"></i> Pedidos de Suporte</h2>

    <?php include __DIR__ . '/_avisos.php'; ?>

    <?php if (!empty($erros)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars(implode(' ', $erros)) ?></div>
    <?php endif; ?>

    <?php if (empty($tickets)): ?>
        <p class="text-muted">Nenhum pedido de suporte.</p>
    <?php endif; ?>

    <?php foreach ($tickets as $ticket): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title mb-1">#<?= $ticket['id'] ?> — <?= htmlspecialchars($ticket['assunto']) ?></h5>
                    <span>
                        <?php if ($ticket['atrasado']): ?><span class="badge bg-danger">Prazo vencido</span><?php endif; ?>
                        <span class="badge bg-<?= $ticket['status'] === 'Respondido' ? 'success' : 'warning' ?>"><?= $ticket['status'] ?></span>
                    </span>
                </div>
                <p class="small text-muted mb-2">
                    <?= htmlspecialchars($ticket['usuario_nome'] ?? 'conta removida') ?> (<?= $ticket['tipo_usuario'] ?>)
                    · <?= htmlspecialchars($ticket['usuario_email'] ?? '') ?>
                    · aberto em <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                    <?php if ($ticket['status'] === 'Aberto'): ?>
                        · responder até <?= date('d/m/Y', strtotime($ticket['prazo'])) ?>
                    <?php endif; ?>
                </p>
                <p><?= nl2br(htmlspecialchars($ticket['mensagem'])) ?></p>

                <?php if ($ticket['status'] === 'Respondido'): ?>
                    <div class="border-start border-success border-3 ps-3">
                        <div class="small text-muted">Respondido em <?= date('d/m/Y H:i', strtotime($ticket['respondido_em'])) ?></div>
                        <?= nl2br(htmlspecialchars($ticket['resposta'])) ?>
                    </div>
                <?php else: ?>
                    <form method="POST" action="/admin/suporte/responder">
                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                        <label for="resposta_<?= $ticket['id'] ?>" class="form-label">Resposta</label>
                        <textarea class="form-control mb-2" id="resposta_<?= $ticket['id'] ?>" name="resposta" rows="3" maxlength="5000" required></textarea>
                        <button type="submit" class="btn btn-primary btn-sm">Responder</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
