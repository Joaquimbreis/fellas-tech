<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = conectar();
    $versao = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo '<h1>Conexão OK</h1>';
    echo '<p>Servidor MySQL/MariaDB: ' . htmlspecialchars((string) $versao) . '</p>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>Falha na conexão</h1>';
    echo '<p>Tipo: ' . htmlspecialchars(get_class($e)) . '</p>';
    // Detalhes só aparecem em ambiente local ou em erros nossos (mensagens seguras)
    if ($e instanceof RuntimeException || env('APP_ENV') === 'local') {
        echo '<p>Código: ' . htmlspecialchars((string) $e->getCode()) . '</p>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}