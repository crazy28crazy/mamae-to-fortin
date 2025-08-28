<?php
// Integração com o sistema principal usando config.php
require __DIR__ . '/config.php';

// Adiciona o header para forçar a codificação UTF-8, corrigindo problemas com acentos.
header('Content-Type: text/html; charset=utf-8');

// Verifica se há um usuário logado para buscar suas informações e funções.
$user = null;
if (!empty($_SESSION['user_id'])) {
    $user = current_user($pdo);
}

$stmt = $pdo->query("SELECT * FROM Plano ORDER BY preco ASC");
$planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Planos para Personal Trainers</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
      <h2>Planos para Personal Trainers</h2>
      <p>Escolha o plano ideal para gerenciar seus alunos e aulas.</p>
      <hr>
      <?php if (empty($planos)): ?>
          <p>Nenhum plano disponível no momento.</p>
      <?php else: ?>
          <?php foreach ($planos as $plano): ?>
              <div class="plano" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; border-radius: 8px;">
                  <h3 style="color: white;">Plano <?= htmlspecialchars($plano["nome_plano"]) ?></h3>
                  
                  <ul style="list-style-position: inside;">
                      <?php
                      // Transforma a descrição do banco (separada por '|') em uma lista de benefícios.
                      $beneficios = explode('|', $plano['descricao']);
                      foreach ($beneficios as $beneficio):
                      ?>
                          <li style="color: white;"><?= htmlspecialchars($beneficio) ?></li>
                      <?php endforeach; ?>
                  </ul>

                  <p><strong>Preço: R$ <?= number_format((float)$plano["preco"], 2, ',', '.') ?> / mês</strong></p>

                  <?php
                  // LÓGICA DE FUNÇÃO ATUALIZADA:
                  // Mostra o botão de contratar apenas para usuários logados com a função 'PersonalTrainer'.
                  $funcoes = explode(',', $user['funcoes'] ?? '');
                  if ($user && in_array('PersonalTrainer', $funcoes)):
                  ?>
                      <a href="pagamento.php?id_plano=<?= $plano['id_plano'] ?>" class="btn">Contratar Plano</a>
                  <?php endif; ?>
              </div>
          <?php endforeach; ?>
      <?php endif; ?>
      <p><a href="index.php" class="btn">Voltar</a></p>
  </div>
</body>
</html>
