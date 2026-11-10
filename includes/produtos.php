<?php
declare(strict_types=1);

/** Formata um valor em reais (apenas para EXIBIR; cálculos usam o valor do banco). */
function moeda(string|float|int $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

/**
 * Endereço da imagem do produto. Se o arquivo não existir (ou não houver imagem),
 * devolve a imagem padrão "sem imagem".
 */
function imagem_produto(?string $arquivo): string
{
    if ($arquivo !== null && $arquivo !== '') {
        $nome = basename($arquivo);   // basename: impede caminhos como ../
        if (is_file(dirname(__DIR__) . '/assets/img/produtos/' . $nome)) {
            return url('assets/img/produtos/' . rawurlencode($nome));
        }
    }
    return url('assets/img/sem-imagem.svg');
}

/** Todas as categorias, em ordem alfabética. */
function listar_categorias(): array
{
    return conectar()->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();
}

/**
 * Produtos ATIVOS da vitrine, com busca opcional (nome ou descrição) e filtro por categoria.
 * Os disponíveis aparecem antes dos esgotados.
 */
function buscar_produtos(string $busca = '', ?int $categoriaId = null): array
{
    $sql = 'SELECT p.id, p.nome, p.preco, p.estoque, p.imagem, c.nome AS categoria
            FROM produtos p
            JOIN categorias c ON c.id = p.categoria_id
            WHERE p.ativo = 1';
    $params = [];

    if ($busca !== '') {
        // Escapa % e _ digitados pelo usuário, para serem tratados como texto comum
        $termo = '%' . addcslashes($busca, '%_\\') . '%';
        $sql .= ' AND (p.nome LIKE :q_nome OR p.descricao LIKE :q_desc)';
        $params[':q_nome'] = $termo;   // nomes diferentes: o PDO não deixa repetir o mesmo
        $params[':q_desc'] = $termo;
    }
    if ($categoriaId !== null) {
        $sql .= ' AND p.categoria_id = :categoria';
        $params[':categoria'] = $categoriaId;
    }

    $sql .= ' ORDER BY (p.estoque = 0) ASC, p.nome ASC';

    $stmt = conectar()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Um produto ativo pelo id (ou null se não existir ou estiver desativado). */
function buscar_produto(int $id): ?array
{
    $stmt = conectar()->prepare(
        'SELECT p.id, p.nome, p.descricao, p.preco, p.estoque, p.imagem,
                p.categoria_id, c.nome AS categoria
         FROM produtos p
         JOIN categorias c ON c.id = p.categoria_id
         WHERE p.id = :id AND p.ativo = 1'
    );
    $stmt->execute([':id' => $id]);
    $produto = $stmt->fetch();
    return $produto === false ? null : $produto;
}