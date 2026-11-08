<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Devolve a conexão PDO com o MySQL. Cria uma vez e reaproveita nas chamadas seguintes.
 * Nenhuma credencial aparece aqui: tudo vem do .env.
 */
function conectar(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    carregar_env(dirname(__DIR__) . '/.env');

    $host    = env('DB_HOST', 'localhost');
    $porta   = env('DB_PORT', '3306');
    $banco   = env('DB_NAME');
    $usuario = env('DB_USER');
    $senha   = env('DB_PASS', '');

    if ($banco === null || $usuario === null) {
        throw new RuntimeException('Defina DB_NAME e DB_USER no arquivo .env.');
    }

    $dsn = "mysql:host={$host};port={$porta};dbname={$banco};charset=utf8mb4";

    $pdo = new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // erros viram exceções
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,                    // prepared statements reais
    ]);

    return $pdo;
}