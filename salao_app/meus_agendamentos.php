<?php
/** US06 – Consultar e cancelar os próprios agendamentos (Sprint 2). Acesso: cliente. */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/agendamento.php';
$u   = exigir_login(['CLIENTE']);
$pdo = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $erro = cancelar_agendamento($pdo, (int) ($_POST['numero'] ?? 0), $u['codigo']);
    flash($erro === '' ? 'sucesso' : 'erro', $erro === '' ? 'Agendamento cancelado.' : $erro);
    header('Location: meus_agendamentos.php');
    exit;
}

$st = $pdo->prepare(
    'SELECT a.numero, a.data_hora, a.status, a.valor_total, s.nome AS servico, p.nome AS profissional,
            (a.data_hora > NOW()) AS futuro
       FROM agendamento a
       JOIN servico s ON s.codigo = a.servico_codigo
       JOIN pessoa  p ON p.codigo = a.profissional_codigo
      WHERE a.cliente_codigo = :c
      ORDER BY a.data_hora DESC'
);
$st->execute([':c' => $u['codigo']]);
$lista = $st->fetchAll();

cabecalho('Meus agendamentos');
?>
<p><a class="botao" href="agendar.php">+ Novo agendamento</a></p>
<section class="cartao">
<?php if (!$lista): ?>
    <p>Você ainda não possui agendamentos.</p>
<?php else: ?>
    <table>
        <tr><th>Data/hora</th><th>Serviço</th><th>Profissional</th><th>Valor</th><th>Situação</th><th></th></tr>
        <?php foreach ($lista as $a): ?>
        <tr>
            <td><?= h(date('d/m/Y H:i', strtotime($a['data_hora']))) ?></td>
            <td><?= h($a['servico']) ?></td><td><?= h($a['profissional']) ?></td>
            <td>R$ <?= h(number_format((float) $a['valor_total'], 2, ',', '.')) ?></td>
            <td class="status-<?= h($a['status']) ?>"><?= h(ucfirst(strtolower($a['status']))) ?></td>
            <td><?php if ($a['status'] === 'AGENDADO' && $a['futuro']): ?>
                <form method="post" onsubmit="return confirm('Cancelar este agendamento?');">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="numero" value="<?= (int) $a['numero'] ?>">
                    <button class="botao-perigo" type="submit" style="margin:0;padding:6px 12px">Cancelar</button>
                </form>
            <?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</section>
<?php rodape();
