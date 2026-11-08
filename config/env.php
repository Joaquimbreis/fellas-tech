<?php
declare(strict_types=1);

/**
 * Lê o arquivo .env e guarda cada variável em $_ENV.
 * Linhas vazias e comentários (#) são ignorados.
 */
function carregar_env(string $caminho): void
{
    if (!is_readable($caminho)) {
        throw new RuntimeException(
            'Arquivo .env não encontrado. Copie o .env.example para .env e preencha os valores.'
        );
    }

    $conteudo = file_get_contents($caminho);
    $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo); // remove BOM, se houver

    foreach (preg_split('/\R/', $conteudo) as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#' || !str_contains($linha, '=')) {
            continue;
        }

        [$chave, $valor] = explode('=', $linha, 2);
        $chave = trim($chave);
        $valor = trim($valor);

        // Remove aspas simples ou duplas ao redor do valor
        if (strlen($valor) >= 2
            && ($valor[0] === '"' || $valor[0] === "'")
            && $valor[0] === substr($valor, -1)) {
            $valor = substr($valor, 1, -1);
        }

        $_ENV[$chave] = $valor;
    }
}

/** Devolve o valor de uma variável do .env (ou o padrão, se não existir). */
function env(string $chave, ?string $padrao = null): ?string
{
    return $_ENV[$chave] ?? $padrao;
}