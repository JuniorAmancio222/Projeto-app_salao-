<?php
/** US01 – Login e autenticação de usuários (Sprint 1). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if (usuario_logado()) {
    header('Location: painel.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = conectar()->prepare(
        'SELECT codigo, nome, senha, perfil FROM pessoa WHERE login = :login'
    );
    $stmt->execute([':login' => $login]);
    $pessoa = $stmt->fetch();

    // password_verify compara a senha digitada com o hash gravado no banco
    if ($pessoa && password_verify($senha, $pessoa['senha'])) {
        session_regenerate_id(true);          // evita fixação de sessão
        $_SESSION['usuario'] = [
            'codigo' => (int) $pessoa['codigo'],
            'nome'   => $pessoa['nome'],
            'perfil' => $pessoa['perfil'],
        ];
        header('Location: painel.php');
        exit;
    }
    // Mensagem genérica: não revela se o erro foi no login ou na senha
    $erro = 'Login ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar – Salão</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<main class="login cartao">
    <h1>✂ Entrar</h1>
    <?php if ($erro): ?><div class="aviso erro"><?= h($erro) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <?= csrf_campo() ?>
        <label for="login">Login</label>
        <input id="login" name="login" required autofocus>
        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" required>
        <button type="submit">Entrar</button>
    </form>
</main>
</body>
</html>
