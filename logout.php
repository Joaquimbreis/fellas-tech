<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

// Só sai com POST e token válido (impede que outro site force o logout)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    redirecionar('index.php');
}

encerrar_sessao();
flash('info', 'Você saiu da sua conta.');
redirecionar('index.php');