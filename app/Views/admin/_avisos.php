<?php if (($_GET['status'] ?? '') === 'ok'): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Ação registrada.</div>
<?php elseif (($_GET['error'] ?? '') === 'nao_alterado'): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Nada foi alterado: o registro não existe mais ou já estava nessa situação.</div>
<?php endif; ?>
<nav class="mb-4">
    <a href="/admin" class="btn btn-sm btn-outline-secondary">Painel</a>
    <a href="/admin/denuncias" class="btn btn-sm btn-outline-secondary">Denúncias</a>
    <a href="/admin/vagas" class="btn btn-sm btn-outline-secondary">Vagas</a>
    <a href="/admin/usuarios" class="btn btn-sm btn-outline-secondary">Usuários</a>
    <a href="/admin/categorias" class="btn btn-sm btn-outline-secondary">Categorias</a>
    <a href="/admin/administradores" class="btn btn-sm btn-outline-secondary">Administradores</a>
</nav>
