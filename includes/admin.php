<?php
declare(strict_types=1);

/** Funções do painel administrativo (carregar só nas páginas de /admin). */

require_once __DIR__ . '/pedidos.php';   // usa centavos_para_decimal() e FORMAS_PAGAMENTO

const IMAGEM_MAX_BYTES = 2 * 1024 * 1024;                       // 2 MB
const TIPOS_IMAGEM = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

/** Menu do painel. $ativa: resumo, produtos ou pedidos. */
function admin_menu(string $ativa): void
{
    $itens = [
        'resumo'   => ['Resumo',   'admin/index.php'],
        'produtos' => ['Produtos', 'admin/produtos.php'],
        'pedidos'  => ['Pedidos',  'admin/pedidos.php'],
    ];
    echo '<ul class="nav nav-pills mb-4">';
    foreach ($itens as $chave => [$rotulo, $destino]) {
        $classe = $chave === $ativa ? 'nav-link active' : 'nav-link';
        echo '<li class="nav-item"><a class="' . $classe . '" href="' . e(url($destino)) . '">' . e($rotulo) . '</a></li>';
    }
    echo '</ul>';
}

/** Números da página inicial do painel. */
function admin_resumo(): array
{
    return conectar()->query(
        "SELECT
            (SELECT COUNT(*) FROM produtos WHERE ativo = 1)                    AS ativos,
            (SELECT COUNT(*) FROM produtos)                                     AS produtos,
            (SELECT COUNT(*) FROM produtos WHERE ativo = 1 AND estoque = 0)     AS esgotados,
            (SELECT COUNT(*) FROM pedidos)                                      AS pedidos,
            (SELECT COALESCE(CAST(ROUND(SUM(total) * 100) AS UNSIGNED), 0) FROM pedidos) AS faturamento_centavos,
            (SELECT COUNT(*) FROM usuarios WHERE perfil = 'cliente')            AS clientes"
    )->fetch();
}

/** Todos os produtos (ativos e desativados) para a tabela do painel. */
function admin_listar_produtos(): array
{
    return conectar()->query(
        'SELECT p.id, p.nome, p.estoque, p.imagem, p.ativo,
                CAST(ROUND(p.preco * 100) AS UNSIGNED) AS preco_centavos,
                c.nome AS categoria
         FROM produtos p
         JOIN categorias c ON c.id = p.categoria_id
         ORDER BY p.ativo DESC, p.nome ASC'
    )->fetchAll();
}

/** Um produto qualquer (mesmo desativado), para o formulário de edição. */
function admin_buscar_produto(int $id): ?array
{
    $stmt = conectar()->prepare(
        'SELECT id, categoria_id, nome, descricao, preco, estoque, imagem, ativo
         FROM produtos WHERE id = :id'
    );
    $stmt->execute([':id' => $id]);
    $produto = $stmt->fetch();
    return $produto === false ? null : $produto;
}

/**
 * "129,90", "129.9" ou "129" -> centavos. Devolve null se o formato for inválido
 * ou o valor for zero. Não aceita separador de milhar (ex.: 1.299,90).
 */
function preco_para_centavos(string $texto): ?int
{
    if (preg_match('/^(\d{1,7})(?:[.,](\d{1,2}))?$/', trim($texto), $m) !== 1) {
        return null;
    }
    $centavos = (int) $m[1] * 100 + (isset($m[2]) ? (int) str_pad($m[2], 2, '0') : 0);
    return $centavos > 0 ? $centavos : null;
}

/**
 * Valida os campos do formulário de produto.
 * Devolve [dados_limpos, lista_de_erros]. Só os campos esperados são lidos.
 */
function validar_produto(array $post, array $idsCategorias): array
{
    $erros = [];

    $nome      = trim((string) ($post['nome'] ?? ''));
    $descricao = trim((string) ($post['descricao'] ?? ''));
    $categoria = filter_var($post['categoria_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $preco     = preco_para_centavos((string) ($post['preco'] ?? ''));
    $estoque   = filter_var($post['estoque'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100000]]);

    if (mb_strlen($nome) < 2 || mb_strlen($nome) > 150) {
        $erros[] = 'O nome deve ter de 2 a 150 caracteres.';
    }
    if ($descricao === '' || mb_strlen($descricao) > 2000) {
        $erros[] = 'A descrição é obrigatória (até 2000 caracteres).';
    }
    if ($categoria === false || !in_array($categoria, $idsCategorias, true)) {
        $erros[] = 'Escolha uma categoria válida.';
    }
    if ($preco === null) {
        $erros[] = 'Preço inválido. Use números com vírgula ou ponto nos centavos (ex.: 129,90), sem separador de milhar.';
    }
    if ($estoque === false) {
        $erros[] = 'O estoque deve ser um número inteiro entre 0 e 100000.';
    }

    $dados = [
        'nome'         => $nome,
        'descricao'    => $descricao,
        'categoria_id' => $categoria === false ? 0 : $categoria,
        'preco'        => $preco === null ? '' : centavos_para_decimal($preco),
        'estoque'      => $estoque === false ? 0 : $estoque,
        'ativo'        => isset($post['ativo']) ? 1 : 0,
    ];
    return [$dados, $erros];
}

/**
 * Recebe a imagem enviada (opcional). Confere o tamanho, o tipo REAL do arquivo
 * (não o nome) e gera um nome novo no servidor. Devolve [nome_do_arquivo|null, erro|null].
 */
function receber_imagem(?array $arquivo): array
{
    if ($arquivo === null || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];                                     // nenhuma imagem enviada
    }
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Não foi possível enviar a imagem (código ' . (int) $arquivo['error'] . ').'];
    }
    if ($arquivo['size'] > IMAGEM_MAX_BYTES) {
        return [null, 'A imagem deve ter no máximo 2 MB.'];
    }

    $temporario = $arquivo['tmp_name'];
    if (!is_uploaded_file($temporario)) {
        return [null, 'Envio de arquivo inválido.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporario);
    if (!isset(TIPOS_IMAGEM[$mime]) || @getimagesize($temporario) === false) {
        return [null, 'Envie uma imagem JPG, PNG ou WEBP.'];
    }

    $nome    = 'p_' . bin2hex(random_bytes(8)) . '.' . TIPOS_IMAGEM[$mime];   // nome gerado por nós
    $destino = dirname(__DIR__) . '/assets/img/produtos/' . $nome;
    if (!move_uploaded_file($temporario, $destino)) {
        return [null, 'Não foi possível salvar a imagem no servidor.'];
    }
    return [$nome, null];
}

/** Apaga uma imagem, mas SÓ se foi enviada pelo painel (nome gerado: p_xxxxxxxxxxxxxxxx.ext). */
function apagar_imagem_enviada(?string $nome): void
{
    if ($nome !== null && preg_match('/^p_[a-f0-9]{16}\.(jpg|png|webp)$/', $nome) === 1) {
        $caminho = dirname(__DIR__) . '/assets/img/produtos/' . $nome;
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }
}

/** Insere (id null) ou atualiza um produto. Devolve o id. */
function salvar_produto(?int $id, array $d, ?string $imagemNova): int
{
    $pdo = conectar();

    if ($id === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem, ativo)
             VALUES (:categoria, :nome, :descricao, :preco, :estoque, :imagem, :ativo)'
        );
        $stmt->execute([
            ':categoria' => $d['categoria_id'], ':nome' => $d['nome'], ':descricao' => $d['descricao'],
            ':preco' => $d['preco'], ':estoque' => $d['estoque'], ':imagem' => $imagemNova, ':ativo' => $d['ativo'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    // COALESCE: sem imagem nova, mantém a imagem que já estava
    $stmt = $pdo->prepare(
        'UPDATE produtos
         SET categoria_id = :categoria, nome = :nome, descricao = :descricao, preco = :preco,
             estoque = :estoque, ativo = :ativo, imagem = COALESCE(:imagem, imagem)
         WHERE id = :id'
    );
    $stmt->execute([
        ':categoria' => $d['categoria_id'], ':nome' => $d['nome'], ':descricao' => $d['descricao'],
        ':preco' => $d['preco'], ':estoque' => $d['estoque'], ':ativo' => $d['ativo'],
        ':imagem' => $imagemNova, ':id' => $id,
    ]);
    return $id;
}

/** Desativa um produto ativo ou reativa um desativado. Devolve o novo estado (1 ou 0) ou null. */
function alternar_produto_ativo(int $id): ?int
{
    $pdo  = conectar();
    $stmt = $pdo->prepare('UPDATE produtos SET ativo = 1 - ativo WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $leitura = $pdo->prepare('SELECT ativo FROM produtos WHERE id = :id');
    $leitura->execute([':id' => $id]);
    $ativo = $leitura->fetchColumn();
    return $ativo === false ? null : (int) $ativo;
}

/** Pedidos mais recentes (todos os clientes), com o nome do cliente. */
function admin_listar_pedidos(int $limite = 200): array
{
    $stmt = conectar()->prepare(
        'SELECT p.id, p.data_pedido, p.forma_pagamento,
                CAST(ROUND(p.total * 100) AS UNSIGNED) AS total_centavos,
                u.nome AS cliente, u.email
         FROM pedidos p
         JOIN usuarios u ON u.id = p.usuario_id
         ORDER BY p.id DESC
         LIMIT :limite'
    );
    $stmt->bindValue(':limite', max(1, $limite), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Itens de vários pedidos de uma vez, agrupados por pedido: [pedido_id => [item, ...]]. */
function admin_itens_dos_pedidos(array $pedidoIds): array
{
    if (!$pedidoIds) {
        return [];
    }
    $marcadores = implode(',', array_fill(0, count($pedidoIds), '?'));
    $stmt = conectar()->prepare(
        "SELECT i.pedido_id, pr.nome, i.quantidade,
                CAST(ROUND(i.preco_unitario * 100) AS UNSIGNED) AS preco_centavos
         FROM itens_pedido i
         JOIN produtos pr ON pr.id = i.produto_id
         WHERE i.pedido_id IN ($marcadores)
         ORDER BY i.pedido_id, i.id"
    );
    $stmt->execute(array_values($pedidoIds));

    $agrupados = [];
    foreach ($stmt->fetchAll() as $item) {
        $agrupados[(int) $item['pedido_id']][] = $item;
    }
    return $agrupados;
}