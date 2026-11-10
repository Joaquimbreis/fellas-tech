<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$voltar = (string) ($_GET['voltar'] ?? $_POST['voltar'] ?? '');

if (esta_logado()) {
    redirecionar(destino_seguro($voltar));
}

$erro  = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');

        $id = ($email === '' || $senha === '') ? null : autenticar($email, $senha);

        if ($id === null) {
            // Mensagem igual para e-mail inexistente e senha errada
            $erro = 'E-mail ou senha inválidos.';
        } else {
            fazer_login($id);
            flash('success', 'Login realizado com sucesso.');
            if ($voltar !== '') {
                redirecionar(destino_seguro($voltar));
            }
            redirecionar(e_admin() ? 'admin/index.php' : 'index.php');
        }
    }
}

$titulo = 'Entrar';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-5">
        <h1 class="h3 mb-3">Entrar</h1>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_campo() ?>
            <input type="hidden" name="voltar" value="<?= e($voltar) ?>">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= e($email) ?>" autocomplete="username" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha"
                       autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>

        <p class="mt-3 text-center">Ainda não tem conta? <a href="<?= e(url('cadastro.php')) ?>">Cadastre-se</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>