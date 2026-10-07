<?php
/**
 * Funções de cadastro de usuários (Pessoa + Cliente/Profissional).
 * A tabela pessoa guarda os dados comuns; a tabela do perfil guarda o resto.
 */
function campos_pessoa_validos(array $d): string
{
    foreach (['nome', 'telefone', 'email', 'login', 'senha'] as $campo) {
        if (trim($d[$campo] ?? '') === '') {
            return 'Preencha todos os campos obrigatórios.';
        }
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        return 'E-mail inválido.';
    }
    if (strlen($d['senha']) < 6) {
        return 'A senha deve ter pelo menos 6 caracteres.';
    }
    return '';
}

/** Insere em pessoa e na tabela do perfil dentro de uma única transação. */
function cadastrar_pessoa(PDO $pdo, array $d, string $perfil, callable $inserirPerfil): string
{
    $erro = campos_pessoa_validos($d);
    if ($erro !== '') {
        return $erro;
    }
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO pessoa
                 (nome, data_nascimento, telefone, email, login, senha, perfil)
             VALUES (:nome, :nasc, :tel, :email, :login, :senha, :perfil)'
        );
        $stmt->execute([
            ':nome'  => trim($d['nome']),
            ':nasc'  => ($d['data_nascimento'] ?? '') ?: null,
            ':tel'   => trim($d['telefone']),
            ':email' => trim($d['email']),
            ':login' => trim($d['login']),
            // a senha é gravada como hash
            ':senha' => password_hash($d['senha'], PASSWORD_DEFAULT),
            ':perfil' => $perfil,
        ]);
        $inserirPerfil((int) $pdo->lastInsertId());
        $pdo->commit();
        return '';
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() === '23000') {      
            return 'Já existe um usuário com este e-mail ou login.';
        }
        throw $e;
    }
}
