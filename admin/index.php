<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin.php';

exigir_admin();   // sem login vai para o login; sem perfil admin recebe 403

$resumo = admin_resumo();

$titulo = 'Painel administrativo';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Painel administrativo</h1>
<?php admin_menu('resumo'); ?>

<div class="row g-3">
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-body-secondary small">Produtos ativos</div>
            <div class="display-6 fw-bold"><?= (int) $resumo['ativos'] ?></div>
            <div class="text-body-secondary small">de <?= (int) $resumo['produtos'] ?> cadastrados</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-body-secondary small">Esgotados</div>
            <div class="display-6 fw-bold"><?= (int) $resumo['esgotados'] ?></div>
            <div class="text-body-secondary small">entre os ativos</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-body-secondary small">Pedidos</div>
            <div class="display-6 fw-bold"><?= (int) $resumo['pedidos'] ?></div>
            <div class="text-body-secondary small"><?= (int) $resumo['clientes'] ?> cliente(s) cadastrado(s)</div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-body-secondary small">Total vendido</div>
            <div class="fs-3 fw-bold"><?= e(moeda_centavos((int) $resumo['faturamento_centavos'])) ?></div>
            <div class="text-body-secondary small">pagamentos simulados</div>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>