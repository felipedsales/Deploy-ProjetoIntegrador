<?php
$title = 'Notificações - Ferraz Conecta';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-bell text-primary"></i> Notificações</h2>

    <?php if (empty($notificacoes)): ?>
        <p class="text-muted">Você ainda não tem notificações.</p>
    <?php endif; ?>

    <div class="list-group">
        <?php foreach ($notificacoes as $notificacao): ?>
            <?php $tag = !empty($notificacao['link']) ? 'a' : 'div'; ?>
            <<?= $tag ?> class="list-group-item <?= $tag === 'a' ? 'list-group-item-action' : '' ?> <?= $notificacao['lida'] ? '' : 'list-group-item-primary' ?>"
                <?= $tag === 'a' ? 'href="' . htmlspecialchars($notificacao['link']) . '"' : '' ?>>
                <div class="d-flex justify-content-between">
                    <strong>
                        <?= htmlspecialchars($notificacao['titulo']) ?>
                        <?php if (!$notificacao['lida']): ?><span class="badge bg-primary">Nova</span><?php endif; ?>
                    </strong>
                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($notificacao['created_at'])) ?></small>
                </div>
                <div><?= htmlspecialchars($notificacao['mensagem']) ?></div>
            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
</div>
