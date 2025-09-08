<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/config.php';
require_role('PersonalTrainer');

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
    /* Estilos para o layout dos planos */
    .planos-container {
        display: flex; /* Ativa o layout flexbox para alinhar os planos horizontalmente */
        justify-content: center; /* Centraliza os planos na página */
        flex-wrap: wrap; /* Permite que os planos quebrem para a linha de baixo */
        gap: 25px; /* Espaço entre os planos */
        text-align: left; /* Alinha o texto dentro de cada plano à esquerda */
    }
    .plano {
        border: 1px solid #00ff66; /* Borda verde para combinar com o tema */
        border-radius: 15px;
        padding: 25px;
        width: 28%; /* Define a largura para caberem 3 por linha */
        background-color: rgba(0, 0, 0, 0.3);
        display: flex; /* Essencial para que todos os planos tenham a mesma altura */
        flex-direction: column; /* Organiza o conteúdo em uma coluna */
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .plano:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 255, 102, 0.2);
    }
    .plano h3 {
        color: #00ff66; /* Título do plano em verde */
        text-align: center;
        min-height: 48px; /* Garante que os títulos ocupem a mesma altura */
    }
    .plano ul {
        list-style-type: '✓ '; /* Adiciona um check antes de cada item */
        padding-left: 20px;
        flex-grow: 1; /* Faz a lista crescer, empurrando o preço e o botão para baixo */
    }
    .plano .preco {
        text-align: center;
        font-size: 1.3em;
        font-weight: bold;
        margin: 20px 0;
    }
    .btn-contratar {
        margin-top: auto; /* Garante que o botão fique sempre na parte inferior */
    }
  </style>
</head>
<body>
  <!-- Usa o container largo para dar mais espaço aos planos -->
  <div class="container container-largo">
    <h2>Planos para Personal Trainers</h2>
    <p>Escolha o plano que melhor se adapta às suas necessidades e comece a gerir os seus alunos.</p>
    <a href="index.php">Voltar</a>
    <hr style="border-color: #444;">
    
    <div class="planos-container">
        <?php foreach ($planos as $plano): ?>
            <div class="plano">
                <h3><?= htmlspecialchars($plano['nome_plano']) ?></h3>
                <ul>
                    <?php foreach (explode(';', $plano['descricao']) as $beneficio): ?>
                        <li><?= htmlspecialchars($beneficio) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="preco">R$ <?= number_format($plano['preco'], 2, ',', '.') ?> / mês</p>
                <a href="checkout.php?id_plano=<?= $plano['id_plano'] ?>" class="btn btn-contratar">Contratar Plano</a>
            </div>
        <?php endforeach; ?>
    </div>
  </div>
</body>
</html>

