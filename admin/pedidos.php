<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/pedidos.php';

exigir_admin();

$pedidos = admin_listar_pedidos(200);
$itens   = admin_itens_dos_pedidos(array_map(static fn(array $p): int => (int) $p['id'], $pedidos));

$titulo = 'Pedidos (painel)';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Painel administrativo</h1>
<?php admin_menu('pedidos'); ?>

<?php if (!$pedidos): ?>
    <div class="alert alert-info">Nenhum pedido realizado ainda.</div>
<?php else: ?>
    <p class="text-body-secondary small">Mostrando os <?= count($pedidos) ?> pedido(s) mais recente(s).</p>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Pagamento</th>
                    <th>Itens</th>
                    <th class="text-end">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td>#<?= (int) $pedido['id'] ?></td>
                    <td class="text-nowrap"><?= e((new DateTime($pedido['data_pedido']))->format('d/m/Y H:i')) ?></td>
                    <td>
                        <?= e($pedido['cliente']) ?>
                        <div class="text-body-secondary small"><?= e($pedido['email']) ?></div>
                    </td>
                    <td><?= e(FORMAS_PAGAMENTO[$pedido['forma_pagamento']] ?? $pedido['forma_pagamento']) ?></td>
                    <td>
                        <ul class="list-unstyled mb-0 small">
                        <?php foreach ($itens[(int) $pedido['id']] ?? [] as $item): ?>
                            <li><?= (int) $item['quantidade'] ?>&times; <?= e($item['nome']) ?>
                                <span class="text-body-secondary">(<?= e(moeda_centavos((int) $item['preco_centavos'])) ?>)</span></li>
                        <?php endforeach; ?>
                        </ul>
                    </td>
                    <td class="text-end fw-semibold"><?= e(moeda_centavos((int) $pedido['total_centavos'])) ?></td>
                    <td class="text-end">
                        <a class="btn btn-outline-light btn-sm"
                           href="<?= e(url('pedido.php?id=' . (int) $pedido['id'])) ?>">Detalhes</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>