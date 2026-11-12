<?php
declare(strict_types=1);

/** Formas de pagamento aceitas (pagamento SIMULADO: nenhum dado de cartão é pedido). */
const FORMAS_PAGAMENTO = [
    'pix'    => 'PIX',
    'boleto' => 'Boleto bancário',
    'cartao' => 'Cartão de crédito',
];

/** Erro de regra de negócio, com mensagem segura para mostrar ao cliente. */
class PedidoException extends RuntimeException
{
}

/** 28990 centavos -> "289.90" (formato do DECIMAL do MySQL). */
function centavos_para_decimal(int $centavos): string
{
    return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
}

/**
 * Cria o pedido numa TRANSAÇÃO: ou grava tudo (pedido, itens e baixa de estoque)
 * ou não grava nada.
 *
 * - Os produtos são travados com FOR UPDATE, então dois clientes comprando a
 *   última unidade ao mesmo tempo não conseguem vender o mesmo estoque duas vezes.
 * - Preço e estoque são lidos do banco aqui, nunca do navegador.
 * - O preço de cada item fica congelado em itens_pedido.preco_unitario.
 *
 * Devolve o id do pedido. Lança PedidoException se faltar estoque.
 */
function criar_pedido(int $usuarioId, string $forma, array $carrinho): int
{
    if (!$carrinho) {
        throw new PedidoException('Seu carrinho está vazio.');
    }
    if (!isset(FORMAS_PAGAMENTO[$forma])) {
        throw new PedidoException('Forma de pagamento inválida.');
    }

    $pdo = conectar();
    $ids = array_keys($carrinho);
    sort($ids);                                   // ordem fixa evita travamento cruzado (deadlock)
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "SELECT id, nome, estoque, CAST(ROUND(preco * 100) AS UNSIGNED) AS preco_centavos
             FROM produtos
             WHERE ativo = 1 AND id IN ($marcadores)
             ORDER BY id
             FOR UPDATE"
        );
        $stmt->execute($ids);

        $produtos = [];
        foreach ($stmt->fetchAll() as $linha) {
            $produtos[(int) $linha['id']] = $linha;
        }

        // 1) Confere TUDO antes de gravar qualquer coisa
        $problemas = [];
        $total     = 0;
        foreach ($carrinho as $id => $quantidade) {
            $produto = $produtos[$id] ?? null;
            if ($produto === null) {
                $problemas[] = 'Um produto do carrinho não está mais disponível.';
            } elseif ((int) $produto['estoque'] < $quantidade) {
                $problemas[] = "{$produto['nome']}: estoque disponível {$produto['estoque']}.";
            } else {
                $total += (int) $produto['preco_centavos'] * $quantidade;
            }
        }
        if ($problemas) {
            throw new PedidoException('Não foi possível concluir o pedido. ' . implode(' ', $problemas));
        }

        // 2) Grava o pedido
        $insPedido = $pdo->prepare(
            'INSERT INTO pedidos (usuario_id, forma_pagamento, total)
             VALUES (:usuario, :forma, :total)'
        );
        $insPedido->execute([
            ':usuario' => $usuarioId,
            ':forma'   => $forma,
            ':total'   => centavos_para_decimal($total),
        ]);
        $pedidoId = (int) $pdo->lastInsertId();

        // 3) Grava os itens (preço congelado) e baixa o estoque
        $insItem = $pdo->prepare(
            'INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario)
             VALUES (:pedido, :produto, :quantidade, :preco)'
        );
        $baixa = $pdo->prepare(
            'UPDATE produtos SET estoque = estoque - :baixa WHERE id = :id AND estoque >= :minimo'
        );

        foreach ($carrinho as $id => $quantidade) {
            $insItem->execute([
                ':pedido'     => $pedidoId,
                ':produto'    => $id,
                ':quantidade' => $quantidade,
                ':preco'      => centavos_para_decimal((int) $produtos[$id]['preco_centavos']),
            ]);
            $baixa->execute([':baixa' => $quantidade, ':id' => $id, ':minimo' => $quantidade]);
            if ($baixa->rowCount() !== 1) {
                throw new RuntimeException('Falha ao baixar o estoque do produto ' . $id);
            }
        }

        $pdo->commit();
        return $pedidoId;
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();                     // desfaz tudo
        }
        throw $erro;
    }
}

/** Um pedido com o nome do cliente (ou null). Valores em centavos. */
function buscar_pedido(int $id): ?array
{
    $stmt = conectar()->prepare(
        'SELECT p.id, p.usuario_id, p.data_pedido, p.forma_pagamento,
                CAST(ROUND(p.total * 100) AS UNSIGNED) AS total_centavos,
                u.nome AS cliente, u.email
         FROM pedidos p
         JOIN usuarios u ON u.id = p.usuario_id
         WHERE p.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $pedido = $stmt->fetch();
    return $pedido === false ? null : $pedido;
}

/** Itens de um pedido, com o preço da hora da compra. */
function buscar_itens_pedido(int $pedidoId): array
{
    $stmt = conectar()->prepare(
        'SELECT i.produto_id, pr.nome, pr.imagem, i.quantidade,
                CAST(ROUND(i.preco_unitario * 100) AS UNSIGNED) AS preco_centavos
         FROM itens_pedido i
         JOIN produtos pr ON pr.id = i.produto_id
         WHERE i.pedido_id = :id
         ORDER BY i.id'
    );
    $stmt->execute([':id' => $pedidoId]);
    return $stmt->fetchAll();
}

/** Pedidos de um cliente, do mais novo para o mais antigo. */
function listar_pedidos_usuario(int $usuarioId): array
{
    $stmt = conectar()->prepare(
        'SELECT p.id, p.data_pedido, p.forma_pagamento,
                CAST(ROUND(p.total * 100) AS UNSIGNED) AS total_centavos,
                (SELECT COALESCE(SUM(quantidade), 0) FROM itens_pedido WHERE pedido_id = p.id) AS itens
         FROM pedidos p
         WHERE p.usuario_id = :usuario
         ORDER BY p.id DESC'
    );
    $stmt->execute([':usuario' => $usuarioId]);
    return $stmt->fetchAll();
}