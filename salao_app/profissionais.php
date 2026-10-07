<?php
/** US03 – Cadastro de profissionais (Sprint 1). Acesso: administrador. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/pessoas.php';
exigir_login(['ADMINISTRADOR']);
$pdo = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $comissao = (float) str_replace(',', '.', $_POST['comissao'] ?? '0');
    if ($comissao < 0 || $comissao > 100) {
        $erro = 'A comissão deve estar entre 0 e 100.';
    } else {
        $erro = cadastrar_pessoa($pdo, $_POST, 'PROFISSIONAL', function (int $codigo) use ($pdo, $comissao) {
            $pdo->prepare(
                'INSERT INTO profissional (codigo, especialidade, comissao, ativo)
                 VALUES (:c, :e, :m, :a)'
            )->execute([
                ':c' => $codigo,
                ':e' => trim($_POST['especialidade'] ?? '') ?: null,
                ':m' => $comissao,
                ':a' => ($_POST['ativo'] ?? 'S') === 'N' ? 'N' : 'S',
            ]);
        });
    }
    flash($erro === '' ? 'sucesso' : 'erro', $erro === '' ? 'Profissional cadastrado com sucesso!' : $erro);
    header('Location: profissionais.php');
    exit;
}

$profissionais = $pdo->query(
    "SELECT p.codigo, p.nome, p.telefone, p.email, f.especialidade, f.comissao, f.ativo
       FROM pessoa p JOIN profissional f ON f.codigo = p.codigo
      ORDER BY p.nome"
)->fetchAll();

cabecalho('Cadastro de profissionais');
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
            <div><label>Especialidade</label><input name="especialidade"></div>
            <div><label>Comissão (%)</label><input name="comissao" value="0"></div>
            <div><label>Situação</label>
                <select name="ativo"><option value="S">Ativo</option><option value="N">Inativo</option></select></div>
        </div>
        <button type="submit">Cadastrar profissional</button>
    </form>
</section>
<section class="cartao">
    <h2>Profissionais cadastrados (<?= count($profissionais) ?>)</h2>
    <table>
        <tr><th>Nome</th><th>Especialidade</th><th>Comissão</th><th>Telefone</th><th>Situação</th></tr>
        <?php foreach ($profissionais as $p): ?>
        <tr><td><?= h($p['nome']) ?></td><td><?= h($p['especialidade']) ?></td>
            <td><?= h(number_format((float) $p['comissao'], 2, ',', '.')) ?>%</td>
            <td><?= h($p['telefone']) ?></td>
            <td><?= $p['ativo'] === 'S' ? 'Ativo' : 'Inativo' ?></td></tr>
        <?php endforeach; ?>
    </table>
</section>
<?php rodape();
