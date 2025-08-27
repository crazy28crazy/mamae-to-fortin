<?php
session_start();
require_once "conexao.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION["id_usuario"];
$isAdmin = false;

// Verifica função do usuário
$stmt = $pdo->prepare("
    SELECT f.descricao 
    FROM usuario_funcao uf
    JOIN funcao f ON f.id_funcao = uf.id_funcao
    WHERE uf.id_usuario = ?
");
$stmt->execute([$id_usuario]);
$funcoes = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (in_array("Admin", $funcoes)) {
    $isAdmin = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idEditar = $isAdmin && !empty($_POST["id_usuario"]) ? $_POST["id_usuario"] : $id_usuario;
    $nome = $_POST["nome"];
    $idade = $_POST["idade"];
    $email = $_POST["email"];

    $stmt = $pdo->prepare("UPDATE Usuario SET nome = ?, idade = ?, email = ? WHERE id_usuario = ?");
    $stmt->execute([$nome, $idade, $email, $idEditar]);
    $mensagem = "Perfil atualizado com sucesso!";
}

// Busca dados
$idExibir = isset($_GET["id"]) && $isAdmin ? $_GET["id"] : $id_usuario;
$stmt = $pdo->prepare("SELECT * FROM Usuario WHERE id_usuario = ?");
$stmt->execute([$idExibir]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Perfil</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
      <h2>Perfil</h2>
      <?php if (!empty($mensagem)): ?>
          <p style="color: lime;"><?= htmlspecialchars($mensagem) ?></p>
      <?php endif; ?>
      <form method="post">
          <?php if ($isAdmin): ?>
              <input type="number" name="id_usuario" placeholder="ID do usuário" value="<?= htmlspecialchars($usuario['id_usuario']) ?>"><br><br>
          <?php endif; ?>
          <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required><br><br>
          <input type="number" name="idade" value="<?= htmlspecialchars($usuario['idade']) ?>"><br><br>
          <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required><br><br>
          <button type="submit" class="btn">Salvar</button>
      </form>
      <p><a href="index.php" class="btn">Voltar</a></p>
  </div>
</body>
</html>
