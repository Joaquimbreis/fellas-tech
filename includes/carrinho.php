<?php
declare(strict_types=1);

/**
 * Carrinho de compras guardado na sessão: [id_do_produto => quantidade].
 * A sessão NÃO guarda preços: eles são sempre lidos do banco.
 */

/** Formata centavos como reais (ex.: 28990 -> "R$ 289,90"). */
function moeda_centavos(int $centavos): string
{
    return 'R$ ' . number_format(intdiv($centavos, 100), 0, '', '.') . ',' . sprintf('%02d', $centavos % 100);
}

/** Conteúdo do carrinho, já limpo (só ids e quantidades inteiros e positivos). */
function carrinho_obter(): array
{
    $bruto  = $_SESSION['carrinho'] ?? [];
    $limpo  = [];
    if (is_array($bruto)) {
        foreach ($bruto as $id => $quantidade) {
            if (is_int($id) && is_int($quantidade) && $id > 0 && $quantidade > 0) {
                $limpo[$id] = $quantidade;
            }
        }
    }
    return $limpo;
}

/** Total de unidades no carrinho (para o contador do menu; não consulta o banco). */
function carrinho_quantidade_total(): int
{
    return array_sum(carrinho_obter());
}

/** Adiciona um produto. Devolve [tipo_da_mensagem, texto]. */
function carrinho_adicionar(int $produtoId, int $quantidade): array
{
    $produto = buscar_produto($produtoId);          // só produtos ativos
    if ($produto === null) {
        return ['danger', 'Produto indisponível.'];
    }
    $estoque = (int) $produto['estoque'];
    if ($estoque === 0) {
        return ['warning', 'Este produto está esgotado.'];
    }

    $atual = carrinho_obter()[$produtoId] ?? 0;
    $novo  = $atual + min(max($quantidade, 1), 1000);

    if ($novo > $estoque) {
        $_SESSION['carrinho'][$produtoId] = $estoque;
        return ['warning', "Só há {$estoque} unidade(s) de {$produto['nome']} em estoque. Quantidade ajustada."];
    }
    $_SESSION['carrinho'][$produtoId] = $novo;
    return ['success', 'Produto adicionado ao carrinho.'];
}

/** Altera a quantidade de um item (0 ou menos remove). */
function carrinho_atualizar(int $produtoId, int $quantidade): array
{
    if (!isset(carrinho_obter()[$produtoId])) {
        return ['warning', 'Esse item não está no carrinho.'];
    }
    if ($quantidade <= 0) {
        unset($_SESSION['carrinho'][$produtoId]);
        return ['info', 'Item removido do carrinho.'];
    }

    $produto = buscar_produto($produtoId);
    if ($produto === null || (int) $produto['estoque'] === 0) {
        unset($_SESSION['carrinho'][$produtoId]);
        return ['warning', 'Produto indisponível: removido do carrinho.'];
    }

    $estoque    = (int) $produto['estoque'];
    $quantidade = min($quantidade, 1000);
    if ($quantidade > $estoque) {
        $_SESSION['carrinho'][$produtoId] = $estoque;
        return ['warning', "Só há {$estoque} unidade(s) em estoque. Quantidade ajustada."];
    }
    $_SESSION['carrinho'][$produtoId] = $quantidade;
    return ['success', 'Quantidade atualizada.'];
}

function carrinho_remover(int $produtoId): void
{
    unset($_SESSION['carrinho'][$produtoId]);
}

function carrinho_limpar(): void
{
    unset($_SESSION['carrinho']);
}

/**
 * Itens do carrinho com dados ATUAIS do banco (nome, preço, estoque).
 * Corrige o carrinho se algo mudou: produto desativado ou esgotado sai,
 * e quantidade acima do estoque é reduzida. Valores em centavos (inteiros),
 * para não ter erro de arredondamento.
 *
 * Devolve ['itens' => [...], 'total' => centavos, 'avisos' => [...]].
 */
function carrinho_itens(): array
{
    $carrinho = carrinho_obter();
    $itens    = [];
    $avisos   = [];
    $total    = 0;

    if ($carrinho) {
        $ids        = array_keys($carrinho);
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = conectar()->prepare(
            "SELECT id, nome, estoque, imagem, CAST(ROUND(preco * 100) AS UNSIGNED) AS preco_centavos
             FROM produtos
             WHERE ativo = 1 AND id IN ($marcadores)"
        );
        $stmt->execute($ids);

        $produtos = [];
        foreach ($stmt->fetchAll() as $linha) {
            $produtos[(int) $linha['id']] = $linha;
        }

        foreach ($carrinho as $id => $quantidade) {
            $produto = $produtos[$id] ?? null;

            if ($produto === null) {
                unset($_SESSION['carrinho'][$id]);
                $avisos[] = 'Um produto não está mais disponível e foi removido do carrinho.';
                continue;
            }

            $estoque = (int) $produto['estoque'];
            if ($estoque === 0) {
                unset($_SESSION['carrinho'][$id]);
                $avisos[] = "{$produto['nome']} esgotou e foi removido do carrinho.";
                continue;
            }
            if ($quantidade > $estoque) {
                $quantidade = $estoque;
                $_SESSION['carrinho'][$id] = $estoque;
                $avisos[] = "A quantidade de {$produto['nome']} foi ajustada para o estoque disponível ({$estoque}).";
            }

            $preco    = (int) $produto['preco_centavos'];
            $subtotal = $preco * $quantidade;
            $total   += $subtotal;

            $itens[] = [
                'id'              => $id,
                'nome'            => $produto['nome'],
                'imagem'          => $produto['imagem'],
                'estoque'         => $estoque,
                'quantidade'      => $quantidade,
                'preco_centavos'  => $preco,
                'subtotal'        => $subtotal,
            ];
        }
    }

    return ['itens' => $itens, 'total' => $total, 'avisos' => $avisos];
}