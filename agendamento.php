<?php
require __DIR__ . '/config.php';
require_login();

$user = current_user($pdo);
// Pega as funções do usuário (ex: 'Aluno,PersonalTrainer') e transforma em um array.
$funcoes = explode(',', $user['funcoes'] ?? '');

// Verifica qual é a função principal do usuário para mostrar a tela certa.
$funcao = '';
if (in_array('PersonalTrainer', $funcoes)) {
    $funcao = 'PersonalTrainer';
} elseif (in_array('Aluno', $funcoes)) {
    $funcao = 'Aluno';
}

// Processa um novo agendamento se o usuário for um Aluno.
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

// Busca a lista de personais juntando as tabelas PersonalTrainer e Usuario para pegar o nome.
$personais_query = $pdo->query("
    SELECT pt.id_personal, u.nome
    FROM PersonalTrainer pt
    JOIN Usuario u ON pt.id_usuario = u.id_usuario
    ORDER BY u.nome
");
$personais = $personais_query->fetchAll();

// Busca os agendamentos dependendo se o usuário é Aluno ou Personal.
$agendamentos = [];
if ($funcao === 'Aluno') {
    $st = $pdo->prepare("
        SELECT a.data_hora, u.nome AS personal_nome
        FROM Agendamento a
        JOIN PersonalTrainer pt ON a.id_personal = pt.id_personal
        JOIN Usuario u ON pt.id_usuario = u.id_usuario
        WHERE a.id_usuario = ?
        ORDER BY a.data_hora DESC
    ");
    $st->execute([$user['id_usuario']]);
    $agendamentos = $st->fetchAll();

} elseif ($funcao === 'PersonalTrainer') {
    $st = $pdo->prepare("
        SELECT a.data_hora, u.nome AS aluno_nome
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
    <a href="index.php">Voltar para a página inicial</a>
    <hr>
    <?php if (!empty($msg)): ?>
      <p style="color: green; font-weight: bold;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if ($funcao === 'Aluno'): ?>
      <h3>Novo Agendamento</h3>
      <p>Utilize o formulário abaixo para marcar uma nova aula com um de nossos personais.</p>
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
      <hr>
      <h3>Seus agendamentos</h3>

    <?php elseif ($funcao === 'PersonalTrainer'): ?>
      <h3>Alunos agendados com você</h3>
      <p>Esta é a lista de aulas agendadas com você. Você não pode criar novos agendamentos por aqui.</p>

    <?php else: ?>
      <h3>Sem permissão</h3>
      <p>Seu perfil não tem permissão para criar ou visualizar agendamentos. Se você for um aluno, entre em contato com o suporte.</p>
    <?php endif; ?>

    <?php if ($funcao === 'Aluno' || $funcao === 'PersonalTrainer'): ?>
      <ul class="list-box">
        <?php if (!empty($agendamentos)): ?>
          <?php foreach ($agendamentos as $a): ?>
            <li>
              <?php if ($funcao === 'Aluno'): ?>
                <strong>Personal:</strong> <?= htmlspecialchars($a['personal_nome']) ?><br>
                <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?>
              <?php else: ?>
                <strong>Aluno:</strong> <?= htmlspecialchars($a['aluno_nome']) ?><br>
                <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?>
              <?php endif; ?>
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
