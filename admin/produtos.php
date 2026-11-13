<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin.php';

exigir_admin();

// Desativar / reativar (não existe exclusão: produtos já vendidos precisam continuar no histórico)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if (!csrf_valido()) {
        flash('danger', 'Sessão expirada. Tente novamente.');
    } elseif (($_POST['acao'] ?? '') === 'alternar' && $id !== false && $id !== null) {
        $ativo = alternar_produto_ativo($id);
        if ($ativo === null) {
            flash('danger', 'Produto não encontrado.');
        } else {
            flash('success', $ativo === 1 ? 'Produto reativado.' : 'Produto desativado: saiu da vitrine.');
        }
    } else {
        flash('danger', 'Solicitação inválida.');
    }
    redirecionar('admin/produtos.php');
}

$produtos = admin_listar_produtos();

$titulo = 'Produtos (painel)';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h3 mb-0">Painel administrativo</h1>
    <a class="btn btn-primary" href="<?= e(url('admin/produto_form.php')) ?>">Novo produto</a>
</div>
<?php admin_menu('produtos'); ?>

<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Categoria</th>
                <th class="text-end">Preço</th>
                <th class="text-center">Estoque</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($produtos as $p): ?>
            <tr class="<?= (int) $p['ativo'] === 1 ? '' : 'opacity-50' ?>">
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="ratio ratio-4x3 bg-white rounded flex-shrink-0" style="width: 64px;">
                            <img src="<?= e(imagem_produto($p['imagem'])) ?>" alt="" class="object-fit-contain p-1">
                        </div>
                        <span><?= e($p['nome']) ?></span>
                    </div>
                </td>
                <td><?= e($p['categoria']) ?></td>
                <td class="text-end"><?= e(moeda_centavos((int) $p['preco_centavos'])) ?></td>
                <td class="text-center">
                    <?php if ((int) $p['estoque'] === 0): ?>
                        <span class="badge text-bg-danger">0</span>
                    <?php else: ?>
                        <?= (int) $p['estoque'] ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ((int) $p['ativo'] === 1): ?>
                        <span class="badge text-bg-success">Ativo</span>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">Desativado</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <div class="d-flex gap-2 justify-content-end">
                        <a class="btn btn-outline-light btn-sm"
                           href="<?= e(url('admin/produto_form.php?id=' . (int) $p['id'])) ?>">Editar</a>
                                         <form method="post" data-confirmar="<?= (int) $p['ativo'] === 1 ? 'Desativar este produto? Ele sairá da vitrine.' : 'Reativar este produto?' ?>">
                     
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="alternar">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button type="submit"
                                    class="btn btn-sm <?= (int) $p['ativo'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                <?= (int) $p['ativo'] === 1 ? 'Desativar' : 'Reativar' ?>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>