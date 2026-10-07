<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/agendamento.php';
$u   = exigir_login(['CLIENTE']);
$pdo = conectar();

// Confirmação do agendamento 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $serv = (int) ($_POST['servico'] ?? 0);
    $prof = (int) ($_POST['profissional'] ?? 0);
    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';

    $erro = criar_agendamento($pdo, $u['codigo'], $prof, $serv, $data, $hora);
    if ($erro === '') {
        flash('sucesso', 'Agendamento realizado com sucesso!');
        header('Location: meus_agendamentos.php');
    } else {
        flash('erro', $erro);
        header('Location: agendar.php?' . http_build_query(
            ['servico' => $serv, 'profissional' => $prof, 'data' => $data]));
    }
    exit;
}

$servicos      = $pdo->query('SELECT codigo, nome, preco, duracao_min FROM servico ORDER BY nome')->fetchAll();
$profissionais = $pdo->query(
    "SELECT p.codigo, p.nome, f.especialidade FROM pessoa p
       JOIN profissional f ON f.codigo = p.codigo
      WHERE f.ativo = 'S' ORDER BY p.nome"
)->fetchAll();

// Consulta de horários disponíveis 
$serv = (int) ($_GET['servico'] ?? 0);
$prof = (int) ($_GET['profissional'] ?? 0);
$data = $_GET['data'] ?? '';
$consultou = $serv && $prof && $data !== '';
$livres = $consultou ? horarios_disponiveis($pdo, $prof, $serv, $data) : [];

cabecalho('Agendar horário');
?>
<section class="cartao">
    <form method="get">
        <div class="grade">
            <div><label>Serviço</label>
                <select name="servico" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($servicos as $s): ?>
                    <option value="<?= (int) $s['codigo'] ?>" <?= $serv === (int) $s['codigo'] ? 'selected' : '' ?>>
                        <?= h($s['nome']) ?> – R$ <?= h(number_format((float) $s['preco'], 2, ',', '.')) ?>
                        (<?= (int) $s['duracao_min'] ?> min)</option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Profissional</label>
                <select name="profissional" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($profissionais as $p): ?>
                    <option value="<?= (int) $p['codigo'] ?>" <?= $prof === (int) $p['codigo'] ? 'selected' : '' ?>>
                        <?= h($p['nome']) ?><?= $p['especialidade'] ? ' – ' . h($p['especialidade']) : '' ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label>Data</label>
                <input type="date" name="data" min="<?= date('Y-m-d') ?>" value="<?= h($data) ?>" required></div>
        </div>
        <button type="submit">Ver horários disponíveis</button>
    </form>
</section>

<?php if ($consultou): ?>
<section class="cartao">
    <h2>Horários disponíveis em <?= h(date('d/m/Y', strtotime($data))) ?></h2>
    <?php if (!$livres): ?>
        <p>Não há horários livres para esta combinação. Tente outra data ou profissional.</p>
    <?php else: ?>
        <form method="post" class="horarios">
            <?= csrf_campo() ?>
            <input type="hidden" name="servico" value="<?= $serv ?>">
            <input type="hidden" name="profissional" value="<?= $prof ?>">
            <input type="hidden" name="data" value="<?= h($data) ?>">
            <?php foreach ($livres as $hora): ?>
                <button type="submit" name="hora" value="<?= h($hora) ?>"><?= h($hora) ?></button>
            <?php endforeach; ?>
        </form>
        <p><small>Clique no horário desejado para confirmar o agendamento.</small></p>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php rodape();
