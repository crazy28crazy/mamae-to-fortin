<?php
require __DIR__ . '/config.php';
require_login();

$user = current_user($pdo);
$msg = '';
$agendamentos = [];
$personais = [];

// --- Lógica de Submissão de Formulário (Apenas para Alunos) ---
if (is_aluno($user) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id_personal = (int)($_POST['id_personal'] ?? 0);
    $data_hora = trim($_POST['data_hora'] ?? '');

    if ($id_personal && $data_hora) {
        try {
            $st = $pdo->prepare("INSERT INTO agendamento (id_usuario, id_personal, data_hora) VALUES (?, ?, ?)");
            $st->execute([$user['id_usuario'], $id_personal, $data_hora]);
            $msg = "Agendamento realizado com sucesso!";
        } catch (PDOException $e) {
            $msg = "Erro ao realizar o agendamento.";
        }
    } else {
        $msg = "Por favor, preencha todos os campos!";
    }
}

// --- Lógica de Visualização de Dados ---

// Se for aluno, busca a lista de personais para o formulário.
if (is_aluno($user)) {
    $personais = $pdo->query("
        SELECT pt.id_personal, u.nome
        FROM personal_trainer pt
        JOIN usuario u ON pt.id_usuario = u.id_usuario
        ORDER BY u.nome
    ")->fetchAll();
}

// Se for aluno, busca os SEUS agendamentos.
if (is_aluno($user)) {
    $st = $pdo->prepare("
        SELECT a.data_hora, u.nome AS personal_nome
        FROM agendamento a
        JOIN personal_trainer pt ON a.id_personal = pt.id_personal
        JOIN usuario u ON pt.id_usuario = u.id_usuario
        WHERE a.id_usuario = ?
        ORDER BY a.data_hora DESC
    ");
    $st->execute([$user['id_usuario']]);
    $agendamentos = $st->fetchAll();
}
// Se for personal, busca os agendamentos feitos COM ELE.
elseif (is_personal($user)) {
    $st = $pdo->prepare("
        SELECT a.data_hora, u.nome AS aluno_nome
        FROM agendamento a
        JOIN usuario u ON u.id_usuario = a.id_usuario
        WHERE a.id_personal = (
            SELECT id_personal FROM personal_trainer WHERE id_usuario = ?
        )
        ORDER BY a.data_hora DESC
    ");
    $st->execute([$user['id_usuario']]);
    $agendamentos = $st->fetchAll();
}

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Agendamentos</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body, .container { color: white; }
    a { color: #87CEFA; }
    hr { border-color: #555; }
    .form-box, .list-box { background-color: #333; padding: 20px; border-radius: 8px; }
    label { display: block; margin-top: 10px; }
    select, input[type="datetime-local"], button {
        width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #555;
    }
    button { background-color: #007bff; color: white; cursor: pointer; }
    ul { list-style-type: none; padding: 0; }
    li { background-color: #444; padding: 10px; margin-bottom: 5px; border-radius: 4px; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Agendamentos</h2>
    <a href="index.php">Voltar para a página inicial</a>
    <hr>
    <?php if ($msg): ?>
      <p style="color: #28a745; font-weight: bold;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if (is_aluno($user)): ?>
      <h3>Novo Agendamento</h3>
      <p>Utilize o formulário abaixo para marcar uma nova aula.</p>
      <form method="post" class="form-box">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <label for="id_personal">Escolha um personal:</label>
        <select name="id_personal" id="id_personal" required>
          <option value="">-- Selecione --</option>
          <?php foreach ($personais as $p): ?>
            <option value="<?= $p['id_personal'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
          <?php endforeach; ?>
        </select>
        <label for="data_hora">Data e Hora:</label>
        <input type="datetime-local" name="data_hora" id="data_hora" required>
        <button type="submit" style="margin-top: 15px;">Agendar</button>
      </form>
      <hr>
      <h3>Seus agendamentos</h3>

    <?php elseif (is_personal($user)): ?>
      <h3>Alunos agendados com você</h3>

    <?php else: ?>
      <h3>Sem permissão</h3>
      <p>O seu perfil não tem permissão para aceder a esta área.</p>
    <?php endif; ?>

    <?php if (is_aluno($user) || is_personal($user)): ?>
      <ul class="list-box">
        <?php if (!empty($agendamentos)): ?>
          <?php foreach ($agendamentos as $a): ?>
            <li>
              <?php if (is_aluno($user)): ?>
                <strong>Personal:</strong> <?= htmlspecialchars($a['personal_nome']) ?><br>
              <?php else: ?>
                <strong>Aluno:</strong> <?= htmlspecialchars($a['aluno_nome']) ?><br>
              <?php endif; ?>
              <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li>Nenhum agendamento encontrado.</li>
        <?php endif; ?>
      </ul>
    <?php endif; ?>
  </div>
</body>
</html>

