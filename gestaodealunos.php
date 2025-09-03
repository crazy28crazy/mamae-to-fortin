<?php
require __DIR__ . '/config.php';
require_role('PersonalTrainer');

$user = current_user($pdo);

// Busca o id_personal do utilizador logado
$stmt_personal_id = $pdo->prepare("SELECT id_personal FROM personal_trainer WHERE id_usuario = ?");
$stmt_personal_id->execute([$user['id_usuario']]);
$id_personal = $stmt_personal_id->fetchColumn();

// Busca todos os alunos que têm agendamentos com este personal
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id_usuario, u.nome, u.email
    FROM usuario u
    JOIN agendamento a ON u.id_usuario = a.id_usuario
    WHERE a.id_personal = ?
    ORDER BY u.nome
");
$stmt->execute([$id_personal]);
$alunos = $stmt->fetchAll();

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Gestão de Alunos</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
      body, .container, th, td { color: white; }
      table { width: 100%; border-collapse: collapse; margin-top: 20px; }
      th, td { border: 1px solid #555; padding: 10px; }
      th { background-color: #333; }
      a { color: #87CEFA; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Meus Alunos</h2>
    <p>Esta é a lista de alunos que já agendaram uma aula com você.</p>
    <a href="index.php">Voltar</a>
    <hr>
    <?php if (empty($alunos)): ?>
        <p>Ainda não há alunos na sua lista.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nome do Aluno</th>
                <th>Email</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alunos as $aluno): ?>
            <tr>
                <td><?= htmlspecialchars($aluno['nome']) ?></td>
                <td><?= htmlspecialchars($aluno['email']) ?></td>
                <td>
                    <a href="anamnese.php?aluno_id=<?= $aluno['id_usuario'] ?>">Ver / Editar Anamnese</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
  </div>
</body>
</html>
