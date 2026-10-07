<?php

function buscar_servico(PDO $pdo, int $codigo): ?array
{
    $st = $pdo->prepare('SELECT * FROM servico WHERE codigo = :c');
    $st->execute([':c' => $codigo]);
    return $st->fetch() ?: null;
}

function horario_ocupado(PDO $pdo, int $profissional, int $servico, DateTime $inicio, DateTime $fim): bool
{
    // 1) Agendamentos já marcados (os cancelados liberam o horário)
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM agendamento
          WHERE profissional_codigo = :prof
            AND status <> 'CANCELADO'
            AND data_hora < :fim
            AND DATE_ADD(data_hora, INTERVAL duracao MINUTE) > :ini"
    );
    $st->execute([
        ':prof' => $profissional,
        ':fim'  => $fim->format('Y-m-d H:i:s'),
        ':ini'  => $inicio->format('Y-m-d H:i:s'),
    ]);
    if ((int) $st->fetchColumn() > 0) {
        return true;
    }

    // 2) Bloqueios manuais (servico_codigo nulo = vale para qualquer serviço)
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM horario_bloqueado
          WHERE profissional_codigo = :prof
            AND (servico_codigo IS NULL OR servico_codigo = :serv)
            AND data_hora_inicio < :fim
            AND data_hora_fim > :ini'
    );
    $st->execute([
        ':prof' => $profissional,
        ':serv' => $servico,
        ':fim'  => $fim->format('Y-m-d H:i:s'),
        ':ini'  => $inicio->format('Y-m-d H:i:s'),
    ]);
    return (int) $st->fetchColumn() > 0;
}

/** Valida uma data no formato AAAA-MM-DD e que não esteja no passado. */
function data_valida(string $data): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d !== false
        && $d->format('Y-m-d') === $data
        && $data >= date('Y-m-d');
}


function horarios_disponiveis(PDO $pdo, int $profissional, int $servico, string $data): array
{
    $s = buscar_servico($pdo, $servico);
    if ($s === null || !data_valida($data)) {
        return [];
    }
    $duracao = (int) $s['duracao_min'];
    $fecha   = new DateTime($data . ' ' . HORA_FECHAMENTO);
    $agora   = new DateTime();
    $livres  = [];

    for ($ini = new DateTime($data . ' ' . HORA_ABERTURA);
         ; $ini->modify('+' . INTERVALO_MIN . ' minutes')) {
        $fim = (clone $ini)->modify("+{$duracao} minutes");
        if ($fim > $fecha) {
            break;                              // não cabe mais no expediente
        }
        if ($ini <= $agora) {
            continue;                           // horário que já passou
        }
        if (!horario_ocupado($pdo, $profissional, $servico, $ini, $fim)) {
            $livres[] = $ini->format('H:i');
        }
    }
    return $livres;
}


function criar_agendamento(PDO $pdo, int $cliente, int $profissional, int $servico, string $data, string $hora): string
{
    $s = buscar_servico($pdo, $servico);
    if ($s === null) {
        return 'Serviço inválido.';
    }
    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare(
            "SELECT codigo FROM profissional
              WHERE codigo = :p AND ativo = 'S' FOR UPDATE"
        );
        $st->execute([':p' => $profissional]);
        if (!$st->fetch()) {
            $pdo->rollBack();
            return 'Profissional indisponível.';
        }

        // Reconfere a disponibilidade já com o profissional travado (US07)
        $livres = horarios_disponiveis($pdo, $profissional, $servico, $data);
        if (!in_array($hora, $livres, true)) {
            $pdo->rollBack();
            return 'Este horário não está mais disponível. Escolha outro.';
        }

        $pdo->prepare(
            "INSERT INTO agendamento
                (data_hora, status, duracao, valor_total,
                 cliente_codigo, profissional_codigo, servico_codigo)
             VALUES (:dh, 'AGENDADO', :dur, :valor, :cli, :prof, :serv)"
        )->execute([
            ':dh'    => $data . ' ' . $hora . ':00',
            ':dur'   => (int) $s['duracao_min'],
            ':valor' => $s['preco'],          // preço vigente na data da marcação
            ':cli'   => $cliente,
            ':prof'  => $profissional,
            ':serv'  => $servico,
        ]);
        $pdo->commit();
        return '';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** US06 – Cancela um agendamento do próprio cliente (apenas futuros e ativos). */
function cancelar_agendamento(PDO $pdo, int $numero, int $cliente): string
{
    $st = $pdo->prepare(
        "UPDATE agendamento SET status = 'CANCELADO'
          WHERE numero = :n AND cliente_codigo = :c
            AND status = 'AGENDADO' AND data_hora > NOW()"
    );
    $st->execute([':n' => $numero, ':c' => $cliente]);
    return $st->rowCount() === 1 ? '' : 'Não foi possível cancelar este agendamento.';
}
