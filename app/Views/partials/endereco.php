<?php
$camposEndereco = $perfilEndereco['campos_endereco'] ?? json_decode($perfilEndereco['endereco_campos'] ?? '', true) ?? [];
?>
<fieldset class="mb-3" id="endereco-perfil">
    <legend>Endereço</legend>
    <?php if (empty($perfilEndereco['endereco_campos']) && !empty($perfilEndereco['endereco'])): ?>
        <p>Endereço atual: <?= htmlspecialchars($perfilEndereco['endereco']) ?>. Para substituí-lo, informe o CEP abaixo.</p>
    <?php endif; ?>
    <p class="form-text">Digite o CEP para buscar o endereço e acrescente o número. Se necessário, complete os campos manualmente.</p>
    <div class="row">
        <?php foreach (['cep' => 'CEP', 'numero' => 'Número', 'logradouro' => 'Rua ou avenida', 'bairro' => 'Bairro', 'cidade' => 'Cidade', 'uf' => 'UF'] as $campo => $rotulo): ?>
            <div class="col-md-6 mb-2">
                <label class="form-label" for="<?= $campo ?>"><?= $rotulo ?></label>
                <input class="form-control" id="<?= $campo ?>" name="<?= $campo ?>"
                    value="<?= htmlspecialchars($camposEndereco[$campo] ?? '') ?>"
                    <?php if ($campo === 'cep'): ?>inputmode="numeric" autocomplete="postal-code" maxlength="9" pattern="[0-9]{5}-?[0-9]{3}"<?php endif; ?>
                    <?php if ($campo === 'uf'): ?>maxlength="2"<?php endif; ?>>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="form-text" role="status" id="status-cep"></p>
</fieldset>
<script src="/js/endereco.js" defer></script>
