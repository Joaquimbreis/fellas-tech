<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (esta_logado()) {
    redirecionar('index.php');
}

$erros = [];
$nome  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $nome        = trim((string) ($_POST['nome'] ?? ''));
        $email       = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha       = (string) ($_POST['senha'] ?? '');
        $confirmacao = (string) ($_POST['confirmacao'] ?? '');

        if (mb_strlen($nome) < 2 || mb_strlen($nome) > 100) {
            $erros[] = 'Informe seu nome (de 2 a 100 caracteres).';
        }
        if ($email === '' || mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um e-mail válido.';
        }
        if (strlen($senha) < 8) {
            $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
        } elseif (strlen($senha) > 72) {
            $erros[] = 'A senha pode ter no máximo 72 caracteres.';
        } elseif (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
            $erros[] = 'A senha deve conter letras e números.';
        }
        if ($senha !== $confirmacao) {
            $erros[] = 'A confirmação da senha não confere.';
        }

        if (!$erros) {
            $pdo  = conectar();
            $stmt = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = :email');
            $stmt->execute([':email' => $email]);

            if ($stmt->fetchColumn() !== false) {
                $erros[] = 'Este e-mail já está cadastrado.';
            } else {
                try {
                    // O perfil NUNCA vem do formulário: todo cadastro novo é "cliente".
                    $ins = $pdo->prepare(
                        "INSERT INTO usuarios (nome, email, senha_hash, perfil)
                         VALUES (:nome, :email, :hash, 'cliente')"
                    );
                    $ins->execute([
                        ':nome'  => $nome,
                        ':email' => $email,
                        ':hash'  => password_hash($senha, PASSWORD_DEFAULT),
                    ]);
                    flash('success', 'Cadastro realizado! Entre com seu e-mail e senha.');
                    redirecionar('login.php');
                } catch (PDOException $ex) {
                    // 23000 = e-mail duplicado (duas pessoas cadastrando ao mesmo tempo)
                    if ((string) $ex->getCode() === '23000') {
                        $erros[] = 'Este e-mail já está cadastrado.';
                    } else {
                        throw $ex;
                    }
                }
            }
        }
    }
}

$titulo = 'Cadastro';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-5">
        <h1 class="h3 mb-3">Criar conta</h1>

        <?php if ($erros): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($erros as $erro): ?>
                        <li><?= e($erro) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_campo() ?>
            <div class="mb-3">
                <label for="nome" class="form-label">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" maxlength="100"
                       value="<?= e($nome) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" maxlength="150"
                       value="<?= e($email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha"
                       autocomplete="new-password" required>
                <div class="form-text">Mínimo de 8 caracteres, com letras e números.</div>
            </div>
            <div class="mb-3">
                <label for="confirmacao" class="form-label">Confirmar senha</label>
                <input type="password" class="form-control" id="confirmacao" name="confirmacao"
                       autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
        </form>

        <p class="mt-3 text-center">Já tem conta? <a href="<?= e(url('login.php')) ?>">Entrar</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>