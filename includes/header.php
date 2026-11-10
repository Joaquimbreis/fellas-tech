<?php
/** Cabeçalho comum a todas as páginas. Defina $titulo antes de incluir. */
$usuario = usuario_atual();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($titulo ?? 'Início') . ' | Fellas Tech') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e(url('index.php')) ?>">Fellas Tech</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <?php if ($usuario): ?>
                    <li class="nav-item"><span class="navbar-text">Olá, <?= e($usuario['nome']) ?></span></li>
                    <?php if ($usuario['perfil'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/index.php')) ?>">Painel</a></li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <form method="post" action="<?= e(url('logout.php')) ?>" class="d-inline">
                            <?= csrf_campo() ?>
                            <button type="submit" class="btn btn-outline-light btn-sm">Sair</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>">Entrar</a></li>
                    <li class="nav-item"><a class="btn btn-primary btn-sm" href="<?= e(url('cadastro.php')) ?>">Cadastrar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4 flex-grow-1">
<?php foreach (flash_obter() as $mensagem): ?>
    <div class="alert alert-<?= e($mensagem['tipo']) ?> alert-dismissible fade show" role="alert">
        <?= e($mensagem['mensagem']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
<?php endforeach; ?>