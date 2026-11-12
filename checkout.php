<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/pedidos.php';

exigir_login();                       // visitante vai ao login e volta para cá depois
$usuario = usuario_atual();

if (!carrinho_obter()) {
    flash('info', 'Seu carrinho está vazio.');
    redirecionar('carrinho.php');
}

$erro           = null;
$formaEscolhida = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $formaEscolhida = (string) ($_POST['forma_pagamento'] ?? '');

        if (!isset(FORMAS_PAGAMENTO[$formaEscolhida])) {
            $erro = 'Escolha uma forma de pagamento.';
        } else {
            try {
                $pedidoId = criar_pedido((int) $usuario['id'], $formaEscolhida, carrinho_obter());
                carrinho_limpar();
                flash('success', 'Pedido realizado com sucesso!');
                redirecionar('pedido.php?id=' . $pedidoId);
            } catch (PedidoException $ex) {
                $erro = $ex->getMessage();                    // ex.: falta de estoque
            } catch (Throwable $ex) {
                error_log('Falha ao criar pedido: ' . $ex->getMessage());
                $erro = 'Não foi possível finalizar o pedido agora. Tente novamente.';
            }
        }
    }
}

// Dados atuais do banco (corrige o carrinho se algo mudou)
$dados = carrinho_itens();
if (!$dados['itens']) {
    flash('warning', 'Os itens do seu carrinho não estão mais disponíveis.');
    redirecionar('carrinho.php');
}

$titulo = 'Finalizar compra';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Finalizar compra</h1>

<?php if ($erro): ?>
    <div class="alert alert-danger"><?= e($erro) ?></div>
<?php endif; ?>
<?php foreach ($dados['avisos'] as $aviso): ?>
    <div class="alert alert-warning"><?= e($aviso) ?></div>
<?php endforeach; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <h2 class="h5">Resumo do pedido</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="text-center">Qtd.</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($dados['itens'] as $item): ?>
                    <tr>
                        <td><?= e($item['nome']) ?>
                            <div class="text-body-secondary small"><?= e(moeda_centavos($item['preco_centavos'])) ?> cada</div>
                        </td>
                        <td class="text-center"><?= $item['quantidade'] ?></td>
                        <td class="text-end"><?= e(moeda_centavos($item['subtotal'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-end">Total</th>
                        <th class="text-end fs-5"><?= e(moeda_centavos($dados['total'])) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <a href="<?= e(url('carrinho.php')) ?>" class="btn btn-outline-light btn-sm">Voltar ao carrinho</a>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h2 class="h5">Pagamento</h2>
                <p class="text-body-secondary small mb-1">Cliente: <?= e($usuario['nome']) ?> (<?= e($usuario['email']) ?>)</p>
                <p class="text-body-secondary small">
                    Esta loja é um projeto acadêmico: o pagamento é <strong>simulado</strong> e
                    nenhum dado de cartão é solicitado.
                </p>

                <form method="post">
                    <?= csrf_campo() ?>
                    <?php foreach (FORMAS_PAGAMENTO as $valor => $nome): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="forma_pagamento"
                                   id="pg-<?= e($valor) ?>" value="<?= e($valor) ?>"
                                   <?= $formaEscolhida === $valor ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="pg-<?= e($valor) ?>"><?= e($nome) ?></label>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary w-100 mt-3">
                        Confirmar pedido &middot; <?= e(moeda_centavos($dados['total'])) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>