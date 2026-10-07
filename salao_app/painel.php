<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
$u = exigir_login();
cabecalho('Painel');
?>
<p>Olá, <strong><?= h($u['nome']) ?></strong>! Escolha uma opção:</p>
<nav class="menu">
<?php if ($u['perfil'] === 'ADMINISTRADOR'): ?>
    <a href="clientes.php">Cadastrar clientes</a>
    <a href="profissionais.php">Cadastrar profissionais</a>
    <a href="servicos.php">Cadastrar serviços</a>
<?php elseif ($u['perfil'] === 'CLIENTE'): ?>
    <a href="agendar.php">Agendar horário</a>
    <a href="meus_agendamentos.php">Meus agendamentos</a>
<?php else: ?>
    <p>A visualização da agenda do profissional será entregue na Sprint 3.</p>
<?php endif; ?>
</nav>
<?php rodape();
