<?php
session_start();
require_once "conexao.php";

$stmt = $pdo->query("SELECT * FROM Plano");
$planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Planos</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
      <h2>Nossos Planos</h2>
      <?php foreach ($planos as $plano): ?>
          <div class="plano">
              <h3><?= htmlspecialchars($plano["nome"]) ?></h3>
              <p><?= htmlspecialchars($plano["descricao"]) ?></p>
              <p><strong>Preço: R$ <?= htmlspecialchars($plano["preco"]) ?></strong></p>
          </div>
      <?php endforeach; ?>
      <p><a href="index.php" class="btn">Voltar</a></p>
  </div>
</body>
</html>
