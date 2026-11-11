<?php
declare(strict_types=1);

/**
 * Carregado no começo de TODAS as páginas: banco, funções, sessão e autenticação.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/produtos.php';
require_once __DIR__ . '/carrinho.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('FELLASSESS');
    session_set_cookie_params([
        'lifetime' => 0,                                  // some ao fechar o navegador
        'path'     => url(''),                            // só vale para esta loja
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,                               // JavaScript não lê o cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}