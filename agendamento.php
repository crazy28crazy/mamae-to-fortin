<?php
require __DIR__ . '/config.php';
require_login();

// Define o fuso horário para o Brasil para garantir que a hora atual esteja correta
date_default_timezone_set('America/Sao_Paulo');

$user = current_user($pdo);
$msg = '';
$erro = false;

// Processa um novo agendamento se o utilizador for um Aluno
if (is_aluno($user) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id_personal = (int)($_POST['id_personal'] ?? 0);
    $data_hora_selecionada = trim($_POST['data_hora'] ?? '');

    // Converte a data selecionada e a data atual para objetos que podem ser comparados
    $data_selecionada_obj = new DateTime($data_hora_selecionada);
    $data_atual_obj = new DateTime();

    if (!$id_personal || !$data_hora_selecionada) {
        $msg = "Por favor, preencha todos os campos!";
        $erro = true;
    } elseif ($data_selecionada_obj < $data_atual_obj) {
        // --- VALIDAÇÃO PRINCIPAL AQUI ---
        // Se a data selecionada for anterior à data atual, mostra um erro
        $msg = "Não é possível agendar uma aula para uma data ou hora que já passou.";
        $erro = true;
    } else {
        // Se a validação passar, insere o agendamento no banco de dados
        $st = $pdo->prepare("INSERT INTO agendamento (id_usuario, id_personal, data_hora) VALUES (?, ?, ?)");
        $st->execute([$user['id_usuario'], $id_personal, $data_hora_selecionada]);
        $msg = "Agendamento realizado com sucesso!";
    }
}

// Busca a lista de personais para o formulário
$personais_query = $pdo->query("
    SELECT pt.id_personal, u.nome
    FROM personal_trainer pt
    JOIN usuario u ON pt.id_usuario = u.id_usuario
    ORDER BY u.nome
");
$personais = $personais_query->fetchAll();

// Busca os agendamentos existentes para exibição
$agendamentos = [];
if (is_aluno($user)) {
    // Se for aluno, busca os seus agendamentos
    $st = $pdo->prepare("
        SELECT a.data_hora, u.nome AS personal_nome
        FROM agendamento a
        JOIN personal_trainer pt ON a.id_personal = pt.id_personal
        JOIN usuario u ON pt.id_usuario = u.id_usuario
        WHERE a.id_usuario = ? ORDER BY a.data_hora DESC
    ");
    $st->execute([$user['id_usuario']]);
    $agendamentos = $st->fetchAll();
} elseif (is_personal($user)) {
    // Se for personal, busca os agendamentos dos seus alunos
    $st = $pdo->prepare("
        SELECT a.data_hora, u.nome AS aluno_nome
        FROM agendamento a
        JOIN usuario u ON u.id_usuario = a.id_usuario
        WHERE a.id_personal = (SELECT id_personal FROM personal_trainer WHERE id_usuario = ?)
        ORDER BY a.data_hora DESC
    ");
    $st->execute([$user['id_usuario']]);
    $agendamentos = $st->fetchAll();
}

// Formata a data e hora atual para usar no atributo 'min' do input
$data_minima_para_input = date('Y-m-d\TH:i');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Agendamentos</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Estilos para a página de agendamento */
    table { width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left; }
    th, td { border: 1px solid #555; padding: 10px; }
    th { background-color: rgba(0, 0, 0, 0.3); color: #00ff66; }
    label { display: block; margin-top: 15px; text-align: left; }
    select, input[type="datetime-local"] { width: 100%; padding: 10px; }
  </style>
</head>
<body>
  <div class="container container-largo">
    <h2>Agendamentos</h2>
    <a href="index.php">Voltar para a página inicial</a>
    <hr>
    <?php if ($msg): ?>
      <p style="color: <?= $erro ? '#ff4d4d' : '#28a745' ?>; font-weight: bold;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if (is_aluno($user)): ?>
      <h3>Novo Agendamento</h3>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <label for="id_personal">Escolha um personal:</label>
        <select name="id_personal" id="id_personal" required>
          <option value="">-- Selecione --</option>
          <?php foreach ($personais as $p): ?>
            <option value="<?= $p['id_personal'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
          <?php endforeach; ?>
        </select>

        <label for="data_hora">Data e Hora:</label>
        <!-- O atributo 'min' impede a seleção de datas passadas -->
        <input type="datetime-local" name="data_hora" id="data_hora" required min="<?= $data_minima_para_input ?>">
        
        <button type="submit" class="btn">Agendar</button>
      </form>
      <hr>
      <h3>Seus agendamentos</h3>
    <?php elseif (is_personal($user)): ?>
      <h3>Alunos agendados com você</h3>
    <?php endif; ?>

    <?php if (is_aluno($user) || is_personal($user)): ?>
      <table>
        <thead>
            <tr>
                <th><?= is_aluno($user) ? 'Personal' : 'Aluno' ?></th>
                <th>Data e Hora</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($agendamentos)): ?>
                <tr><td colspan="2">Nenhum agendamento encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($agendamentos as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['personal_nome'] ?? $a['aluno_nome']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>

