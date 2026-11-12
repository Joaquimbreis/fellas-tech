<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/pedidos.php';

exigir_login();
$usuario = usuario_atual();
$pedidos = listar_pedidos_usuario((int) $usuario['id']);

$titulo = 'Meus pedidos';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Meus pedidos</h1>

<?php if (!$pedidos): ?>
    <div class="alert alert-info">Você ainda não fez nenhum pedido.</div>
    <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Ver produtos</a>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Data</th>
                    <th>Pagamento</th>
                    <th class="text-center">Itens</th>
                    <th class="text-end">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td>#<?= (int) $pedido['id'] ?></td>
                    <td><?= e((new DateTime($pedido['data_pedido']))->format('d/m/Y H:i')) ?></td>
                    <td><?= e(FORMAS_PAGAMENTO[$pedido['forma_pagamento']] ?? $pedido['forma_pagamento']) ?></td>
                    <td class="text-center"><?= (int) $pedido['itens'] ?></td>
                    <td class="text-end"><?= e(moeda_centavos((int) $pedido['total_centavos'])) ?></td>
                    <td class="text-end">
                        <a class="btn btn-outline-light btn-sm" href="<?= e(url('pedido.php?id=' . (int) $pedido['id'])) ?>">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>