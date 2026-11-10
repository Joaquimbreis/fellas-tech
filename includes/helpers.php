<?php
declare(strict_types=1);

/** Escapa texto para exibir em HTML (proteção contra XSS). */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Monta uma URL do site a partir da pasta do projeto (ex.: /fellas-tech/login.php).
 * Funciona em qualquer subpasta, sem precisar escrever o endereço fixo.
 */
function url(string $caminho = ''): string
{
    static $base = null;
    if ($base === null) {
        $raiz    = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
        $projeto = str_replace('\\', '/', dirname(__DIR__));
        $base    = str_starts_with(strtolower($projeto), strtolower($raiz))
            ? substr($projeto, strlen($raiz))
            : '';
    }
    return $base . '/' . ltrim($caminho, '/');
}

/** Caminho da página atual relativo ao projeto (ex.: admin/index.php). */
function caminho_atual(): string
{
    $uri  = $_SERVER['REQUEST_URI'] ?? '';
    $base = url('');
    return str_starts_with($uri, $base) ? substr($uri, strlen($base)) : '';
}

/** Redireciona para uma página do site e encerra o script. */
function redirecionar(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

/**
 * Aceita só caminhos internos simples (ex.: admin/index.php ou produto.php?id=3).
 * Qualquer outra coisa (endereços externos, "//", caracteres estranhos) volta ao padrão.
 */
function destino_seguro(?string $destino, string $padrao = 'index.php'): string
{
    if ($destino === null || $destino === '' || str_contains($destino, '//')) {
        return $padrao;
    }
    if (preg_match('#^[A-Za-z0-9_\-/]+(\.php)?(\?[A-Za-z0-9_=&\-]*)?$#D', $destino) !== 1) {
        return $padrao;
    }
    return $destino;
}

/** Guarda uma mensagem para aparecer na próxima página (success, danger, warning, info). */
function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/** Devolve as mensagens guardadas e as apaga. */
function flash_obter(): array
{
    $mensagens = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensagens;
}

/** Token CSRF da sessão (cria na primeira vez). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Campo oculto para colocar dentro de todo formulário POST. */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Confere o token enviado no formulário com o da sessão. */
function csrf_valido(): bool
{
    $enviado = $_POST['csrf'] ?? '';
    return is_string($enviado)
        && isset($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $enviado);
}