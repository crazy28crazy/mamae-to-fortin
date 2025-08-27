<?php
require_once "conexao.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST["nome"];
    $cpf = $_POST["cpf"];
    $idade = $_POST["idade"];
    $email = $_POST["email"];
    $senha = password_hash($_POST["senha"], PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO Usuario (nome, cpf, idade, email, senha) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $cpf, $idade, $email, $senha]);
        header("Location: login.php");
        exit;
    } catch (PDOException $e) {
        $erro = "Erro: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Cadastro</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
      <h2>Cadastro</h2>
      <?php if (!empty($erro)): ?>
          <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <form method="post">
          <input type="text" name="nome" placeholder="Nome" required><br><br>
          <input type="text" name="cpf" placeholder="CPF" required><br><br>
          <input type="number" name="idade" placeholder="Idade"><br><br>
          <input type="email" name="email" placeholder="E-mail" required><br><br>
          <input type="password" name="senha" placeholder="Senha" required><br><br>
          <button type="submit" class="btn">Cadastrar</button>
      </form>
      <p><a href="login.php" class="btn">Já tenho conta</a></p>
  </div>
</body>
</html>
