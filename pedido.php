<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/pedidos.php';

exigir_login();
$usuario = usuario_atual();

$id     = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$pedido = ($id === false || $id === null) ? null : buscar_pedido($id);

// Só o dono do pedido (ou um administrador) pode ver. Para os outros: 404,
// que não revela nem se o pedido existe.
if ($pedido === null || ((int) $pedido['usuario_id'] !== (int) $usuario['id'] && !e_admin())) {
    http_response_code(404);
    $titulo = 'Pedido não encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-warning">Pedido não encontrado.</div>';
    echo '<a class="btn btn-outline-light" href="' . e(url('index.php')) . '">Voltar à loja</a>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$itens = buscar_itens_pedido((int) $pedido['id']);
$data  = (new DateTime($pedido['data_pedido']))->format('d/m/Y H:i');
$forma = FORMAS_PAGAMENTO[$pedido['forma_pagamento']] ?? $pedido['forma_pagamento'];

$titulo = 'Pedido #' . $pedido['id'];
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-1">Pedido #<?= (int) $pedido['id'] ?></h1>
<p class="text-body-secondary">
    Realizado em <?= e($data) ?> &middot; Cliente: <?= e($pedido['cliente']) ?> &middot;
    Pagamento: <?= e($forma) ?> (simulado)
</p>

<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Produto</th>
                <th class="text-end">Preço unitário</th>
                <th class="text-center">Qtd.</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($itens as $item): ?>
            <tr>
                <td><?= e($item['nome']) ?></td>
                <td class="text-end"><?= e(moeda_centavos((int) $item['preco_centavos'])) ?></td>
                <td class="text-center"><?= (int) $item['quantidade'] ?></td>
                <td class="text-end"><?= e(moeda_centavos((int) $item['preco_centavos'] * (int) $item['quantidade'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total</th>
                <th class="text-end fs-5"><?= e(moeda_centavos((int) $pedido['total_centavos'])) ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Continuar comprando</a>
    <a class="btn btn-outline-light" href="<?= e(url('meus_pedidos.php')) ?>">Meus pedidos</a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>