<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/produtos.php';

// Filtros vindos da URL (GET), sempre validados
$busca = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);

$categoriaId = filter_var($_GET['categoria'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$categoriaId = $categoriaId === false ? null : $categoriaId;

$categorias = listar_categorias();
$produtos   = buscar_produtos($busca, $categoriaId);
$filtrando  = $busca !== '' || $categoriaId !== null;

$titulo = 'Produtos';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4">
    <h1 class="h3">Periféricos e acessórios</h1>
    <p class="text-secondary mb-0">Teclados, mouses, áudio, monitores e tudo para o seu setup.</p>
</div>

<form method="get" class="row g-2 mb-4">
    <div class="col-12 col-md-5">
        <label for="q" class="visually-hidden">Buscar produto</label>
        <input type="search" class="form-control" id="q" name="q" maxlength="100"
               placeholder="Buscar por nome ou descrição" value="<?= e($busca) ?>">
    </div>
    <div class="col-12 col-md-4">
        <label for="categoria" class="visually-hidden">Categoria</label>
        <select class="form-select" id="categoria" name="categoria">
            <option value="">Todas as categorias</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= (int) $categoria['id'] ?>"
                    <?= $categoriaId === (int) $categoria['id'] ? 'selected' : '' ?>>
                    <?= e($categoria['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-6 col-md-2 d-grid">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-6 col-md-1 d-grid">
        <a class="btn btn-outline-light" href="<?= e(url('index.php')) ?>">Limpar</a>
    </div>
</form>

<p class="text-secondary small">
    <?= count($produtos) ?> produto(s) <?= $filtrando ? 'encontrado(s)' : 'disponível(is)' ?>
</p>

<?php if (!$produtos): ?>
    <div class="alert alert-info">
        Nenhum produto encontrado. Tente outro termo ou
        <a href="<?= e(url('index.php')) ?>" class="alert-link">veja todos os produtos</a>.
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($produtos as $produto): ?>
            <?php
            $link = url('produto.php?id=' . (int) $produto['id']);
            $esgotado = (int) $produto['estoque'] === 0;
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 shadow-sm">
            <a href="<?= e($link) ?>" class="ratio ratio-4x3 bg-white rounded-top">
    <img src="<?= e(imagem_produto($produto['imagem'])) ?>"
         alt="<?= e($produto['nome']) ?>"
         class="object-fit-contain p-2" loading="lazy">
</a>
                    <div class="card-body d-flex flex-column">
                        <span class="badge text-bg-secondary align-self-start mb-2"><?= e($produto['categoria']) ?></span>
                        <h2 class="h6 card-title"><?= e($produto['nome']) ?></h2>
                        <p class="fs-5 fw-bold mt-auto mb-2"><?= e(moeda($produto['preco'])) ?></p>
                        <?php if ($esgotado): ?>
                            <span class="badge text-bg-danger align-self-start mb-2">Esgotado</span>
                        <?php endif; ?>
                        <a href="<?= e($link) ?>" class="btn btn-primary btn-sm">Ver detalhes</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>