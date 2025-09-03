<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/config.php';
require_role('PersonalTrainer'); // <-- Esta linha protege a página

$user = current_user($pdo);
$planos = $pdo->query("SELECT * FROM plano ORDER BY preco ASC")->fetchAll();
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Planos para Personal Trainers</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body, .container, h2, h3, p, strong, li { color: white; }
    .planos-container { display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; }
    .plano { border: 1px solid #555; border-radius: 8px; padding: 20px; width: 30%; background-color: #2c2f33; display: flex; flex-direction: column; }
    .plano ul { list-style-position: inside; padding-left: 0; flex-grow: 1; }
    .btn-contratar { display: block; width: 100%; text-align: center; margin-top: 15px; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Planos para Personal Trainers</h2>
    <p>Escolha o plano que melhor se adapta às suas necessidades e comece a gerir os seus alunos.</p>
    <a href="index.php">Voltar</a>
    <hr>
    
    <div class="planos-container">
        <?php foreach ($planos as $plano): ?>
            <div class="plano">
                <h3><?= htmlspecialchars($plano['nome_plano']) ?></h3>
                <ul>
                    <?php foreach (explode(';', $plano['descricao']) as $beneficio): ?>
                        <li><?= htmlspecialchars($beneficio) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Preço: R$ <?= number_format($plano['preco'], 2, ',', '.') ?> / mês</strong></p>

                <a href="checkout.php?id_plano=<?= $plano['id_plano'] ?>" class="btn btn-contratar">Contratar Plano</a>
            </div>
        <?php endforeach; ?>
    </div>
  </div>
</body>
</html>

