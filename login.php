<?php
session_start();
// Usa o config.php para a conexão com o banco de dados, o que é uma prática melhor.
require_once "config.php";

// Se o usuário já estiver logado, redireciona para a página inicial.
if (!empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $senha = $_POST["senha"];

    $stmt = $pdo->prepare("SELECT id_usuario, nome, senha FROM Usuario WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario["senha"])) {
        // CORREÇÃO: A sessão agora é '_id' para ser consistente com o resto do sistema.
        $_SESSION["user_id"] = $usuario["id_usuario"];
        $_SESSION["nome"] = $usuario["nome"];
        header("Location: index.php");
        exit;
    } else {
        $erro = "E-mail ou senha inválidos.";
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
      <h2>Login</h2>
      <?php if (!empty($erro)): ?>
          <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <form method="post">
          <input type="email" name="email" placeholder="E-mail" required><br><br>
          <input type="password" name="senha" placeholder="Senha" required><br><br>
          <button type="submit" class="btn">Entrar</button>
      </form>
      <p><a href="cadastro.php" class="btn">Cadastrar</a></p>
  </div>
</body>
</html>
