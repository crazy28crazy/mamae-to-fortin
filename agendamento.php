<?php
require __DIR__ . '/config.php';
require_login();

$user = current_user($pdo);

// Verifica função
$st = $pdo->prepare("SELECT f.descricao FROM Funcao f JOIN Usuario u ON f.id_funcao = u.id_funcao WHERE u.id_usuario = ?");
$st->execute([$user['id_usuario']]);
$funcao = $st->fetchColumn();

// Processa novo agendamento (apenas alunos)
if ($funcao === 'Aluno' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id_personal = (int)($_POST['id_personal'] ?? 0);
    $data_hora = trim($_POST['data_hora'] ?? '');

    if ($id_personal && $data_hora) {
        $st = $pdo->prepare("INSERT INTO Agendamento (id_usuario, id_personal, data_hora) VALUES (?, ?, ?)");
        $st->execute([$user['id_usuario'], $id_personal, $data_hora]);
        $msg = "Agendamento realizado com sucesso!";
    } else {
        $msg = "Preencha todos os campos!";
    }
}

// Lista de personais (para aluno escolher)
$personais = $pdo->query("SELECT id_personal, nome FROM PersonalTrainer")->fetchAll();

// Agendamentos (dependendo do papel do usuário)
if ($funcao === 'Aluno') {
    $st = $pdo->prepare("
        SELECT a.*, p.nome AS personal_nome
        FROM Agendamento a
        JOIN PersonalTrainer p ON p.id_personal = a.id_personal
        WHERE a.id_usuario = ?
        ORDER BY a.data_hora DESC
    ");
    $st->execute([$user['id_usuario']]);
    $agendamentos = $st->fetchAll();

} elseif ($funcao === 'PersonalTrainer') {
    $st = $pdo->prepare("
        SELECT a.*, u.nome AS aluno_nome
        FROM Agendamento a
        JOIN Usuario u ON u.id_usuario = a.id_usuario
        WHERE a.id_personal = (
            SELECT id_personal FROM PersonalTrainer WHERE id_usuario = ?
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
</head>
<body>
  <div class="container">
    <h2>Agendamentos</h2>
    <?php if (!empty($msg)): ?>
      <p><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if ($funcao === 'Aluno'): ?>
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

        <button type="submit">Agendar</button>
      </form>
    <?php endif; ?>

    <h3><?= $funcao === 'Aluno' ? 'Seus agendamentos' : 'Seus alunos agendados' ?></h3>
    <ul class="list-box">
      <?php if (!empty($agendamentos)): ?>
        <?php foreach ($agendamentos as $a): ?>
          <li>
            <?php if ($funcao === 'Aluno'): ?>
              <strong><?= htmlspecialchars($a['personal_nome']) ?></strong><br>
              <?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?>
            <?php else: ?>
              <strong><?= htmlspecialchars($a['aluno_nome']) ?></strong><br>
              <?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li>Nenhum agendamento encontrado.</li>
      <?php endif; ?>
    </ul>
  </div>
</body>
</html>
