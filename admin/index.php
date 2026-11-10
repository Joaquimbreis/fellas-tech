<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

exigir_admin();   // sem login vai para o login; sem perfil admin recebe 403

$titulo = 'Painel administrativo';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3">Painel administrativo</h1>
<p class="text-secondary">Área restrita. Aqui entrarão o cadastro de produtos e a lista de pedidos.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>