<?php
$title = 'Candidatura - Ferraz Conecta';
$outroLado = $perfil === 'empresa' ? $candidatura['candidato_nome'] : $candidatura['empresa_nome'];
$voltar = $perfil === 'empresa' ? "/empresa/vagas/{$candidatura['vaga_id']}/candidatos" : '/minhas-candidaturas';
$minha = $avaliacoes[$perfil] ?? null;
$recebida = $avaliacoes[$perfil === 'empresa' ? 'candidato' : 'empresa'] ?? null;
$estrelas = function ($nota) {
    return str_repeat('★', (int) $nota) . str_repeat('☆', 5 - (int) $nota);
};
?>

<div class="container mt-4">
    <h2 class="mb-1"><i class="fas fa-comments text-primary"></i> <?= htmlspecialchars($candidatura['vaga_titulo']) ?></h2>
    <p class="text-muted">
        <?= $perfil === 'empresa' ? 'Candidato' : 'Empresa' ?>: <strong><?= htmlspecialchars($outroLado) ?></strong>
        · Situação da candidatura: <strong><?= htmlspecialchars($candidatura['status']) ?></strong>
    </p>

    <div class="row">
        <div class="col-lg-7 mb-4" id="mensagens">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Mensagens</h5>

                    <?php if (empty($mensagens)): ?>
                        <p class="text-muted">Nenhuma mensagem ainda.</p>
                    <?php endif; ?>

                    <?php foreach ($mensagens as $mensagem): ?>
                        <?php $propria = $mensagem['remetente'] === $perfil; ?>
                        <div class="mb-3 <?= $propria ? 'text-end' : '' ?>">
                            <div class="d-inline-block text-start p-2 rounded <?= $propria ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 85%;">
                                <?= nl2br(htmlspecialchars($mensagem['texto'])) ?>
                            </div>
                            <div class="small text-muted">
                                <?= $propria ? 'Você' : htmlspecialchars($outroLado) ?> · <?= date('d/m/Y H:i', strtotime($mensagem['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!empty($erroMensagem)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erroMensagem) ?></div>
                    <?php endif; ?>

                    <?php if ($permiteMensagens): ?>
                        <form method="POST" action="/candidaturas/<?= $candidatura['id'] ?>/mensagens">
                            <label for="texto" class="form-label">Nova mensagem</label>
                            <textarea class="form-control mb-2" id="texto" name="texto" rows="3" maxlength="2000" required><?= htmlspecialchars($texto ?? '') ?></textarea>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted mb-0">As mensagens ficam disponíveis depois que a candidatura é aceita pela empresa.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4" id="avaliacao">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= $perfil === 'empresa' ? 'Retorno ao candidato' : 'Avaliação da empresa' ?></h5>

                    <?php if (($_GET['status'] ?? '') === 'avaliacao_salva'): ?>
                        <div class="alert alert-success">Avaliação registrada.</div>
                    <?php endif; ?>
                    <?php if (!empty($erroAvaliacao)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erroAvaliacao) ?></div>
                    <?php endif; ?>

                    <?php if ($recebida): ?>
                        <div class="border rounded p-2 mb-3">
                            <div class="small text-muted"><?= $perfil === 'empresa' ? 'Avaliação feita pelo candidato' : 'Retorno da empresa' ?></div>
                            <div aria-label="Nota <?= (int) $recebida['nota'] ?> de 5"><?= $estrelas($recebida['nota']) ?></div>
                            <?php if (!empty($recebida['comentario'])): ?>
                                <div><?= nl2br(htmlspecialchars($recebida['comentario'])) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($permiteAvaliacao): ?>
                        <form method="POST" action="/candidaturas/<?= $candidatura['id'] ?>/avaliacao">
                            <div class="mb-2">
                                <label for="nota" class="form-label required">Nota</label>
                                <select class="form-select" id="nota" name="nota" required>
                                    <option value="">Escolha</option>
                                    <?php for ($n = 5; $n >= 1; $n--): ?>
                                        <option value="<?= $n ?>" <?= (int) ($minha['nota'] ?? 0) === $n ? 'selected' : '' ?>><?= $n ?> — <?= $estrelas($n) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label for="comentario" class="form-label">Comentário (opcional)</label>
                                <textarea class="form-control" id="comentario" name="comentario" rows="3" maxlength="2000"><?= htmlspecialchars($minha['comentario'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-primary"><?= $minha ? 'Atualizar' : 'Enviar' ?></button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted mb-0">A avaliação fica disponível quando o processo seletivo termina.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <a href="<?= $voltar ?>" class="btn btn-outline-secondary">Voltar</a>
</div>
