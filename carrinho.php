<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// ---------------------------------------------------------------
// Ações (POST). Depois de cada ação redireciona, para que atualizar
// a página não repita a operação (padrão Post/Redirect/Get).
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        flash('danger', 'Sessão expirada. Tente novamente.');
        redirecionar('carrinho.php');
    }

    $acao = (string) ($_POST['acao'] ?? '');
    $id   = filter_var($_POST['produto_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $qtd  = filter_var($_POST['quantidade'] ?? null, FILTER_VALIDATE_INT);

    if ($acao === 'adicionar' && $id !== false && $id !== null && $qtd !== false && $qtd >= 1) {
        [$tipo, $texto] = carrinho_adicionar($id, $qtd);
        flash($tipo, $texto);
    } elseif ($acao === 'atualizar' && $id !== false && $id !== null && $qtd !== false) {
        [$tipo, $texto] = carrinho_atualizar($id, $qtd);
        flash($tipo, $texto);
    } elseif ($acao === 'remover' && $id !== false && $id !== null) {
        carrinho_remover($id);
        flash('info', 'Item removido do carrinho.');
    } elseif ($acao === 'limpar') {
        carrinho_limpar();
        flash('info', 'Carrinho esvaziado.');
    } else {
        flash('danger', 'Não foi possível processar a solicitação.');
    }
    redirecionar('carrinho.php');
}

// ---------------------------------------------------------------
// Exibição
// ---------------------------------------------------------------
$dados = carrinho_itens();   // lê preço e estoque do banco e corrige o carrinho se preciso

$titulo = 'Carrinho';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Carrinho de compras</h1>

<?php foreach ($dados['avisos'] as $aviso): ?>
    <div class="alert alert-warning"><?= e($aviso) ?></div>
<?php endforeach; ?>

<?php if (!$dados['itens']): ?>
    <div class="alert alert-info">Seu carrinho está vazio.</div>
    <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Ver produtos</a>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th class="text-end">Preço</th>
                    <th>Quantidade</th>
                    <th class="text-end">Subtotal</th>
                    <th><span class="visually-hidden">Remover</span></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dados['itens'] as $item): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="ratio ratio-4x3 bg-white rounded flex-shrink-0" style="width: 80px;">
                                <img src="<?= e(imagem_produto($item['imagem'])) ?>" alt=""
                                     class="object-fit-contain p-1">
                            </div>
                            <a href="<?= e(url('produto.php?id=' . $item['id'])) ?>"
                               class="link-light text-decoration-none"><?= e($item['nome']) ?></a>
                        </div>
                    </td>
                    <td class="text-end"><?= e(moeda_centavos($item['preco_centavos'])) ?></td>
                    <td>
                        <form method="post" class="d-flex gap-2">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="atualizar">
                            <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
                            <label for="qtd-<?= $item['id'] ?>" class="visually-hidden">Quantidade de <?= e($item['nome']) ?></label>
                            <input type="number" class="form-control form-control-sm" style="width: 5rem;"
                                   id="qtd-<?= $item['id'] ?>" name="quantidade"
                                   value="<?= $item['quantidade'] ?>" min="1" max="<?= $item['estoque'] ?>" required>
                            <button type="submit" class="btn btn-outline-light btn-sm">Atualizar</button>
                        </form>
                    </td>
                    <td class="text-end fw-semibold"><?= e(moeda_centavos($item['subtotal'])) ?></td>
                    <td class="text-end">
                        <form method="post">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="remover">
                            <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Remover</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
        <form method="post">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="limpar">
            <button type="submit" class="btn btn-outline-light btn-sm">Esvaziar carrinho</button>
        </form>
        <div class="text-end">
            <span class="text-body-secondary">Total</span>
            <span class="fs-3 fw-bold ms-2"><?= e(moeda_centavos($dados['total'])) ?></span>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
        <a class="btn btn-outline-light" href="<?= e(url('index.php')) ?>">Continuar comprando</a>
        <?php /* O botão de finalizar compra entra na Etapa 6 */ ?>
        <button type="button" class="btn btn-primary" disabled>Finalizar compra (em breve)</button>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>