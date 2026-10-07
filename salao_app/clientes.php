<?php
/** US02 – Cadastro de clientes (Sprint 1). Acesso: administrador. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/pessoas.php';
exigir_login(['ADMINISTRADOR']);
$pdo = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $erro = cadastrar_pessoa($pdo, $_POST, 'CLIENTE', function (int $codigo) use ($pdo) {
        $pdo->prepare('INSERT INTO cliente (codigo, preferencias) VALUES (:c, :p)')
            ->execute([':c' => $codigo, ':p' => trim($_POST['preferencias'] ?? '') ?: null]);
    });
    flash($erro === '' ? 'sucesso' : 'erro', $erro === '' ? 'Cliente cadastrado com sucesso!' : $erro);
    header('Location: clientes.php');
    exit;
}

$clientes = $pdo->query(
    "SELECT p.codigo, p.nome, p.telefone, p.email, c.data_cadastro
       FROM pessoa p JOIN cliente c ON c.codigo = p.codigo
      ORDER BY p.nome"
)->fetchAll();

cabecalho('Cadastro de clientes');
?>
<section class="cartao">
    <form method="post">
        <?= csrf_campo() ?>
        <div class="grade">
            <div><label>Nome *</label><input name="nome" required></div>
            <div><label>Telefone *</label><input name="telefone" required></div>
            <div><label>E-mail *</label><input name="email" type="email" required></div>
            <div><label>Data de nascimento</label><input name="data_nascimento" type="date"></div>
            <div><label>Login *</label><input name="login" required></div>
            <div><label>Senha * (mín. 6 caracteres)</label><input name="senha" type="password" required></div>
        </div>
        <label>Preferências de atendimento</label>
        <input name="preferencias">
        <button type="submit">Cadastrar cliente</button>
    </form>
</section>
<section class="cartao">
    <h2>Clientes cadastrados (<?= count($clientes) ?>)</h2>
    <table>
        <tr><th>Nome</th><th>Telefone</th><th>E-mail</th><th>Cadastro</th></tr>
        <?php foreach ($clientes as $c): ?>
        <tr><td><?= h($c['nome']) ?></td><td><?= h($c['telefone']) ?></td>
            <td><?= h($c['email']) ?></td>
            <td><?= h(date('d/m/Y', strtotime($c['data_cadastro']))) ?></td></tr>
        <?php endforeach; ?>
    </table>
</section>
<?php rodape();
