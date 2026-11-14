<?php
$title = 'Categorias de Vagas - Ferraz Conecta';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-tags text-primary"></i> Categorias de Vagas</h2>

    <?php include __DIR__ . '/_avisos.php'; ?>

    <?php if (!empty($erros)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erros as $mensagem): ?>
                    <li><?= htmlspecialchars($mensagem) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <p class="text-muted">As empresas escolhem uma destas categorias ao cadastrar a vaga, e os candidatos filtram a busca por elas. Altere os nomes que quiser e salve tudo de uma vez.</p>

    <form method="POST" action="/admin/categorias" id="form-categorias">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Nome</th><th>Vagas</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $categoria): ?>
                        <tr>
                            <td>
                                <input type="text" class="form-control" name="nome[<?= $categoria['id'] ?>]" maxlength="100" required
                                    aria-label="Nome da categoria"
                                    value="<?= htmlspecialchars($enviado['nome'][$categoria['id']] ?? $categoria['nome']) ?>">
                            </td>
                            <td><?= (int) $categoria['total_vagas'] ?></td>
                            <td class="text-end">
                                <button type="submit" form="excluir-<?= $categoria['id'] ?>" class="btn btn-sm btn-outline-danger"
                                    <?= (int) $categoria['total_vagas'] > 0 || count($categorias) <= 1 ? 'disabled title="Categoria em uso ou única do sistema"' : '' ?>>
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>
                            <input type="text" class="form-control" name="nova" maxlength="100" placeholder="Nova categoria (opcional)"
                                aria-label="Nova categoria" value="<?= htmlspecialchars($enviado['nova'] ?? '') ?>">
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar categorias</button>
    </form>

    <?php foreach ($categorias as $categoria): ?>
        <form method="POST" action="/admin/categorias/excluir" id="excluir-<?= $categoria['id'] ?>"
            onsubmit="return confirm('Excluir esta categoria?')">
            <input type="hidden" name="categoria_id" value="<?= $categoria['id'] ?>">
        </form>
    <?php endforeach; ?>
</div>
