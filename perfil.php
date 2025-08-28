<?php
require __DIR__ . '/config.php';
require_login();

$user = current_user($pdo);
$is_admin = is_admin($user);

// Determina qual usuário está sendo editado.
// Admins podem editar outros usuários passando um 'id' na URL.
$id_alvo = $user['id_usuario'];
if ($is_admin && !empty($_GET['id'])) {
    $id_alvo = (int)$_GET['id'];
}

// Processa o formulário de atualização.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    csrf_check();
    // Garante que apenas admins possam mudar o ID do usuário a ser editado.
    $id_editar = $is_admin && !empty($_POST["id_usuario"]) ? (int)$_POST["id_usuario"] : $user['id_usuario'];

    $nome = $_POST["nome"];
    $idade = $_POST["idade"];
    $email = $_POST["email"];

    $stmt = $pdo->prepare("UPDATE Usuario SET nome = ?, idade = ?, email = ? WHERE id_usuario = ?");
    $stmt->execute([$nome, $idade, $email, $id_editar]);
    $mensagem = "Perfil atualizado com sucesso!";

    // Se o usuário editou o próprio perfil, atualiza os dados da sessão.
    if ($id_editar == $user['id_usuario']) {
        $_SESSION['nome'] = $nome;
        $user = current_user($pdo); // Recarrega os dados do usuário.
    }
}

// Busca os dados do usuário a ser exibido no formulário.
$stmt = $pdo->prepare("SELECT * FROM Usuario WHERE id_usuario = ?");
$stmt->execute([$id_alvo]);
$usuario_alvo = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o usuário alvo não for encontrado, exibe um erro.
if (!$usuario_alvo) {
    exit('Usuário não encontrado.');
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Perfil de <?= htmlspecialchars($usuario_alvo['nome']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
      <h2>Perfil</h2>
      <?php if (!empty($mensagem)): ?>
          <p style="color: green;"><?= htmlspecialchars($mensagem) ?></p>
      <?php endif; ?>
      <form method="post">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <!-- O ID do usuário só é editável por um admin -->
          <?php if ($is_admin): ?>
              <label for="id_usuario">ID do Usuário (Apenas Admin):</label>
              <input type="number" id="id_usuario" name="id_usuario" value="<?= htmlspecialchars($usuario_alvo['id_usuario']) ?>"><br><br>
          <?php else: ?>
              <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($usuario_alvo['id_usuario']) ?>">
          <?php endif; ?>

          <label for="nome">Nome:</label>
          <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario_alvo['nome']) ?>" required><br><br>

          <label for="idade">Idade:</label>
          <input type="number" id="idade" name="idade" value="<?= htmlspecialchars($usuario_alvo['idade']) ?>"><br><br>

          <label for="email">E-mail:</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario_alvo['email']) ?>" required><br><br>

          <button type="submit" class="btn">Salvar Alterações</button>
      </form>
      <p><a href="index.php" class="btn">Voltar</a></p>
  </div>
</body>
</html>
