<?php
/**
 * Autenticação por sessão, controle de perfis e proteção CSRF.
 */
session_start();
require_once __DIR__ . '/../config/conexao.php';


function h($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function usuario_logado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/** Bloqueia o acesso de quem não está logado ou não tem o perfil exigido. */
function exigir_login(array $perfis = []): array
{
    $u = usuario_logado();
    if ($u === null) {
        header('Location: login.php');
        exit;
    }
    if ($perfis && !in_array($u['perfil'], $perfis, true)) {
        http_response_code(403);
        exit('Acesso negado para o seu perfil.');
    }
    return $u;
}


function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verificar(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $enviado)) {
        http_response_code(400);
        exit('Requisição inválida (token de segurança incorreto).');
    }
}

/** Mensagens rápidas (sucesso/erro) mostradas na próxima página. */
function flash(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}
