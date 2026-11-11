<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/produtos.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$produto = $id === false || $id === null ? null : buscar_produto($id);

if ($produto === null) {
    http_response_code(404);
    $titulo = 'Produto não encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-warning">Produto não encontrado ou indisponível.</div>';
    echo '<a class="btn btn-outline-light" href="' . e(url('index.php')) . '">Ver todos os produtos</a>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$estoque = (int) $produto['estoque'];

$titulo = $produto['nome'];
require __DIR__ . '/includes/header.php';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Produtos</a></li>
        <li class="breadcrumb-item">
            <a href="<?= e(url('index.php?categoria=' . (int) $produto['categoria_id'])) ?>"><?= e($produto['categoria']) ?></a>
        </li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($produto['nome']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-md-6">
       <div class="ratio ratio-4x3 bg-white rounded">
    <img src="<?= e(imagem_produto($produto['imagem'])) ?>" alt="<?= e($produto['nome']) ?>"
         class="object-fit-contain p-3">
</div>
    </div>
    <div class="col-md-6">
        <span class="badge text-bg-secondary mb-2"><?= e($produto['categoria']) ?></span>
        <h1 class="h3"><?= e($produto['nome']) ?></h1>
        <p class="display-6 fw-bold my-3"><?= e(moeda($produto['preco'])) ?></p>

        <p class="mb-4"><?= nl2br(e($produto['descricao'])) ?></p>

        <p class="mb-3">
            <?php if ($estoque === 0): ?>
                <span class="badge text-bg-danger">Esgotado</span>
            <?php elseif ($estoque <= 5): ?>
                <span class="badge text-bg-warning">Restam apenas <?= $estoque ?> unidade(s)</span>
            <?php else: ?>
                <span class="badge text-bg-success">Em estoque</span>
                <span class="text-secondary small ms-1"><?= $estoque ?> unidades disponíveis</span>
            <?php endif; ?>
        </p>

        <?php if ($estoque > 0): ?>
            <form method="post" action="<?= e(url('carrinho.php')) ?>" class="d-flex flex-wrap gap-2 align-items-center">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="adicionar">
                <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                <label for="quantidade" class="visually-hidden">Quantidade</label>
                <input type="number" class="form-control form-control-lg" style="width: 7rem;"
                       id="quantidade" name="quantidade" value="1" min="1" max="<?= $estoque ?>" required>
                <button type="submit" class="btn btn-primary btn-lg">Adicionar ao carrinho</button>
            </form>
        <?php else: ?>
            <button type="button" class="btn btn-secondary btn-lg" disabled>Produto esgotado</button>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>