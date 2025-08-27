<?php
require __DIR__ . '/config.php';
require_login();

$user = current_user($pdo);

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['destinatario_id'], $_POST['conteudo'])) {
    $st = $pdo->prepare("INSERT INTO Mensagem (id_remetente, id_destinatario, conteudo, data_envio) VALUES (?, ?, ?, NOW())");
    $st->execute([$user['id_usuario'], $_POST['destinatario_id'], $_POST['conteudo']]);
}

// Buscar contatos (todos os usuários exceto eu)
$contatos = $pdo->prepare("SELECT id_usuario, nome FROM Usuario WHERE id_usuario != ?");
$contatos->execute([$user['id_usuario']]);
$contatos = $contatos->fetchAll();

// Buscar mensagens trocadas com alguém específico
$destinatario_id = $_GET['chat'] ?? null;
$mensagens = [];
if ($destinatario_id) {
    $st = $pdo->prepare("
        SELECT m.*, u.nome AS remetente_nome
        FROM Mensagem m
        JOIN Usuario u ON u.id_usuario = m.id_remetente
        WHERE (m.id_remetente = :eu AND m.id_destinatario = :outro)
           OR (m.id_remetente = :outro AND m.id_destinatario = :eu)
        ORDER BY m.data_envio ASC
    ");
    $st->execute(['eu' => $user['id_usuario'], 'outro' => $destinatario_id]);
    $mensagens = $st->fetchAll();
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Chat</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .chat-box {
      position: fixed;
      right: 20px;
      bottom: 0;
      width: 300px;
      max-height: 400px;
      background: #fff;
      border: 2px solid #ccc;
      border-radius: 10px 10px 0 0;
      display: flex;
      flex-direction: column;
      font-size: 14px;
    }
    .chat-header {
      background: #444;
      color: #fff;
      padding: 8px;
      border-radius: 10px 10px 0 0;
      text-align: center;
    }
    .chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 8px;
    }
    .chat-message {
      margin: 5px 0;
      padding: 6px 10px;
      border-radius: 8px;
      max-width: 80%;
      clear: both;
    }
    .me {
      background: #007bff;
      color: #fff;
      margin-left: auto;
    }
    .other {
      background: #eee;
      margin-right: auto;
    }
    .chat-form {
      display: flex;
      padding: 8px;
      border-top: 1px solid #ccc;
    }
    .chat-form input[type="text"] {
      flex: 1;
      padding: 6px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    .chat-form button {
      margin-left: 5px;
      padding: 6px 10px;
    }
    .contact-list {
      padding: 8px;
      border-top: 1px solid #ddd;
      background: #f9f9f9;
    }
    .contact-list a {
      display: block;
      padding: 4px;
      color: #007bff;
      text-decoration: none;
    }
  </style>
</head>
<body>

<div class="chat-box">
  <div class="chat-header">Chat</div>

  <div class="chat-messages">
    <?php if ($destinatario_id && $mensagens): ?>
      <?php foreach ($mensagens as $m): ?>
        <div class="chat-message <?= $m['id_remetente'] == $user['id_usuario'] ? 'me' : 'other' ?>">
          <strong><?= htmlspecialchars($m['remetente_nome']) ?>:</strong><br>
          <?= htmlspecialchars($m['conteudo']) ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align:center; color:#666;">Selecione um contato abaixo</p>
    <?php endif; ?>
  </div>

  <?php if ($destinatario_id): ?>
  <form class="chat-form" method="post">
    <input type="hidden" name="destinatario_id" value="<?= htmlspecialchars($destinatario_id) ?>">
    <input type="text" name="conteudo" placeholder="Digite sua mensagem..." required>
    <button type="submit">➤</button>
  </form>
  <?php endif; ?>

  <div class="contact-list">
    <strong>Contatos</strong>
    <?php foreach ($contatos as $c): ?>
      <a href="?chat=<?= $c['id_usuario'] ?>">
        <?= htmlspecialchars($c['nome']) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>
