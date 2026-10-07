<?php
/** US04 – Cadastro de serviços (Sprint 1). Acesso: administrador. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
exigir_login(['ADMINISTRADOR']);
$pdo = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $nome    = trim($_POST['nome'] ?? '');
    $preco   = (float) str_replace(',', '.', $_POST['preco'] ?? '');
    $duracao = (int) ($_POST['duracao_min'] ?? 0);

    if ($nome === '' || $preco < 0 || $duracao <= 0) {
        flash('erro', 'Informe o nome, um preço válido e a duração em minutos (maior que 0).');
    } else {
        $pdo->prepare(
            'INSERT INTO servico (nome, preco, duracao_min, descricao)
             VALUES (:n, :p, :d, :desc)'
        )->execute([
            ':n' => $nome, ':p' => $preco, ':d' => $duracao,
            ':desc' => trim($_POST['descricao'] ?? '') ?: null,
        ]);
        flash('sucesso', 'Serviço cadastrado com sucesso!');
    }
    header('Location: servicos.php');
    exit;
}

$servicos = $pdo->query('SELECT * FROM servico ORDER BY nome')->fetchAll();

cabecalho('Cadastro de serviços');
?>
<section class="cartao">
    <form method="post">
        <?= csrf_campo() ?>
        <div class="grade">
            <div><label>Nome do serviço *</label><input name="nome" required></div>
            <div><label>Preço (R$) *</label><input name="preco" required placeholder="80,00"></div>
            <div><label>Duração (minutos) *</label><input name="duracao_min" type="number" min="5" step="5" required></div>
        </div>
        <label>Descrição</label>
        <input name="descricao">
        <button type="submit">Cadastrar serviço</button>
    </form>
</section>
<section class="cartao">
    <h2>Serviços cadastrados (<?= count($servicos) ?>)</h2>
    <table>
        <tr><th>Serviço</th><th>Preço</th><th>Duração</th><th>Descrição</th></tr>
        <?php foreach ($servicos as $s): ?>
        <tr><td><?= h($s['nome']) ?></td>
            <td>R$ <?= h(number_format((float) $s['preco'], 2, ',', '.')) ?></td>
            <td><?= (int) $s['duracao_min'] ?> min</td><td><?= h($s['descricao']) ?></td></tr>
        <?php endforeach; ?>
    </table>
</section>
<?php rodape();
