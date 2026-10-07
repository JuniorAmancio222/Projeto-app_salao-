<?php
/** Cabeçalho e rodapé comuns a todas as páginas. */
function cabecalho(string $titulo): void
{
    $u = usuario_logado();
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titulo) ?> – Salão</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<header class="topo">
    <a class="marca" href="painel.php">✂ Salão de Cabeleireiro</a>
    <?php if ($u): ?>
        <span class="usuario"><?= h($u['nome']) ?> (<?= h(strtolower($u['perfil'])) ?>)
            · <a href="logout.php">Sair</a></span>
    <?php endif; ?>
</header>
<main class="conteudo">
    <h1><?= h($titulo) ?></h1>
    <?php if (!empty($_SESSION['flash'])):
        $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="aviso <?= h($f['tipo']) ?>"><?= h($f['texto']) ?></div>
    <?php endif;
}

function rodape(): void
{
    ?>
</main>
</body>
</html>
<?php
}
