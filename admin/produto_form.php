<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin.php';

exigir_admin();

// Sem ?id= é um produto novo; com ?id= é edição
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$id = $id === false ? null : $id;

$produtoAtual = null;
if ($id !== null) {
    $produtoAtual = admin_buscar_produto($id);
    if ($produtoAtual === null) {
        http_response_code(404);
        $titulo = 'Produto não encontrado';
        require __DIR__ . '/../includes/header.php';
        echo '<div class="alert alert-warning">Produto não encontrado.</div>';
        echo '<a class="btn btn-outline-light" href="' . e(url('admin/produtos.php')) . '">Voltar à lista</a>';
        require __DIR__ . '/../includes/footer.php';
        exit;
    }
}

$categorias    = listar_categorias();
$idsCategorias = array_map(static fn(array $c): int => (int) $c['id'], $categorias);

// Valores que aparecem no formulário
$valores = [
    'nome'         => $produtoAtual['nome']         ?? '',
    'descricao'    => $produtoAtual['descricao']    ?? '',
    'categoria_id' => (int) ($produtoAtual['categoria_id'] ?? 0),
    'preco'        => isset($produtoAtual['preco']) ? str_replace('.', ',', $produtoAtual['preco']) : '',
    'estoque'      => (string) ($produtoAtual['estoque'] ?? '0'),
    'ativo'        => (int) ($produtoAtual['ativo'] ?? 1),
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        [$dados, $erros] = validar_produto($_POST, $idsCategorias);

        // Mantém o que foi digitado, para não perder o preenchimento em caso de erro
        $valores = [
            'nome'         => $dados['nome'],
            'descricao'    => $dados['descricao'],
            'categoria_id' => $dados['categoria_id'],
            'preco'        => trim((string) ($_POST['preco'] ?? '')),
            'estoque'      => trim((string) ($_POST['estoque'] ?? '')),
            'ativo'        => $dados['ativo'],
        ];

        if (!$erros) {
            [$imagemNova, $erroImagem] = receber_imagem($_FILES['imagem'] ?? null);
            if ($erroImagem !== null) {
                $erros[] = $erroImagem;
            } else {
                try {
                    $imagemAntiga = $produtoAtual['imagem'] ?? null;
                    $salvoId = salvar_produto($id, $dados, $imagemNova);
                    if ($imagemNova !== null && $id !== null) {
                        apagar_imagem_enviada($imagemAntiga);         // troca: remove a antiga enviada pelo painel
                    }
                    flash('success', $id === null ? 'Produto cadastrado.' : 'Produto atualizado.');
                    redirecionar('admin/produtos.php');
                } catch (PDOException $ex) {
                    apagar_imagem_enviada($imagemNova);               // não deixa arquivo sobrando
                    error_log('Falha ao salvar produto: ' . $ex->getMessage());
                    $erros[] = 'Não foi possível salvar o produto. Tente novamente.';
                }
            }
        }
    }
}

$titulo = $id === null ? 'Novo produto' : 'Editar produto';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3"><?= $id === null ? 'Novo produto' : 'Editar produto' ?></h1>
<?php admin_menu('produtos'); ?>

<?php if ($erros): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($erros as $erro): ?>
                <li><?= e($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate class="row g-3">
    <?= csrf_campo() ?>

    <div class="col-12 col-lg-8">
        <label for="nome" class="form-label">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" maxlength="150"
               value="<?= e($valores['nome']) ?>" required>
    </div>
    <div class="col-12 col-lg-4">
        <label for="categoria_id" class="form-label">Categoria</label>
        <select class="form-select" id="categoria_id" name="categoria_id" required>
            <option value="">Escolha...</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= (int) $categoria['id'] ?>"
                    <?= $valores['categoria_id'] === (int) $categoria['id'] ? 'selected' : '' ?>>
                    <?= e($categoria['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12">
        <label for="descricao" class="form-label">Descrição</label>
        <textarea class="form-control" id="descricao" name="descricao" rows="4" maxlength="2000" required><?= e($valores['descricao']) ?></textarea>
    </div>

    <div class="col-6 col-lg-3">
        <label for="preco" class="form-label">Preço (R$)</label>
        <input type="text" inputmode="decimal" class="form-control" id="preco" name="preco"
               value="<?= e($valores['preco']) ?>" placeholder="129,90" required>
        <div class="form-text">Ex.: 129,90 (sem separador de milhar).</div>
    </div>
    <div class="col-6 col-lg-3">
        <label for="estoque" class="form-label">Estoque</label>
        <input type="number" class="form-control" id="estoque" name="estoque" min="0" max="100000"
               value="<?= e($valores['estoque']) ?>" required>
    </div>
    <div class="col-12 col-lg-6 d-flex align-items-end">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1"
                   <?= $valores['ativo'] === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="ativo">Ativo (aparece na vitrine)</label>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <label for="imagem" class="form-label">Imagem <?= $id === null ? '(opcional)' : '(deixe vazio para manter a atual)' ?></label>
        <input type="file" class="form-control" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp">
        <div class="form-text">JPG, PNG ou WEBP, até 2 MB. Proporção 4:3 (ex.: 800 x 600) fica melhor.</div>
    </div>
    <?php if ($produtoAtual !== null): ?>
        <div class="col-12 col-lg-4">
            <div class="form-label">Imagem atual</div>
            <div class="ratio ratio-4x3 bg-white rounded" style="max-width: 200px;">
                <img src="<?= e(imagem_produto($produtoAtual['imagem'])) ?>" alt="" class="object-fit-contain p-1">
            </div>
        </div>
    <?php endif; ?>

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a class="btn btn-outline-light" href="<?= e(url('admin/produtos.php')) ?>">Cancelar</a>
    </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>