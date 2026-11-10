<?php
declare(strict_types=1);

/**
 * Hash de uma senha qualquer, usado só para gastar o mesmo tempo quando o e-mail
 * não existe (evita descobrir e-mails cadastrados pelo tempo de resposta).
 * O resultado dessa conferência é sempre ignorado.
 */
const HASH_FALSO = '$2y$10$vDrw1cd/MPcFIAJvB6q2wueWJ5Y..9OqBSEiRaAFquANH8edcQPOa';

/**
 * Usuário logado, lido do banco a cada requisição (a sessão guarda só o id).
 * Assim, mudanças de perfil ou exclusão de usuário valem na hora.
 * Use $recarregar = true logo depois de entrar ou sair.
 */
function usuario_atual(bool $recarregar = false): ?array
{
    static $cache = false;
    if ($recarregar) {
        $cache = false;
    }
    if ($cache !== false) {
        return $cache;
    }

    $id = $_SESSION['usuario_id'] ?? null;
    if (!is_int($id)) {
        return $cache = null;
    }

    $stmt = conectar()->prepare('SELECT id, nome, email, perfil FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $usuario = $stmt->fetch();

    if ($usuario === false) {      // usuário apagado: encerra a sessão
        $_SESSION = [];
        return $cache = null;
    }
    return $cache = $usuario;
}

function esta_logado(): bool
{
    return usuario_atual() !== null;
}

function e_admin(): bool
{
    $usuario = usuario_atual();
    return $usuario !== null && $usuario['perfil'] === 'admin';
}

/** Confere e-mail e senha. Devolve o id do usuário ou null. */
function autenticar(string $email, string $senha): ?int
{
    $pdo  = conectar();
    $stmt = $pdo->prepare('SELECT id, senha_hash FROM usuarios WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    $hash = $usuario['senha_hash'] ?? HASH_FALSO;
    $ok   = password_verify($senha, $hash);

    if ($usuario === false || !$ok) {
        return null;
    }

    // Se o algoritmo de hash do PHP evoluiu, atualiza o hash guardado
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $novo = password_hash($senha, PASSWORD_DEFAULT);
        $up   = $pdo->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id');
        $up->execute([':hash' => $novo, ':id' => $usuario['id']]);
    }
    return (int) $usuario['id'];
}

/** Marca o usuário como logado. O novo id de sessão impede "fixação de sessão". */
function fazer_login(int $usuarioId): void
{
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuarioId;
    usuario_atual(true);
}

/** Apaga os dados da sessão e gera um id novo. */
function encerrar_sessao(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
    usuario_atual(true);
}

/** Páginas que exigem login: manda para o login e volta para cá depois. */
function exigir_login(): void
{
    if (!esta_logado()) {
        flash('warning', 'Faça login para continuar.');
        redirecionar('login.php?voltar=' . urlencode(caminho_atual()));
    }
}

/** Páginas só para administrador: sem login vai ao login; sem perfil recebe 403. */
function exigir_admin(): void
{
    exigir_login();
    if (!e_admin()) {
        http_response_code(403);
        $titulo = 'Acesso negado';
        require __DIR__ . '/header.php';
        echo '<div class="alert alert-danger">Você não tem permissão para acessar esta área.</div>';
        echo '<a class="btn btn-outline-light" href="' . e(url('index.php')) . '">Voltar ao início</a>';
        require __DIR__ . '/footer.php';
        exit;
    }
}