<?php
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/config.php';
require_login();

$user = current_user($pdo);
$id_usuario_atual = $user['id_usuario'];

// Lida com o envio de uma nova mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['destinatario_id'], $_POST['conteudo'])) {
    // csrf_check(); // Descomente se tiver a função csrf_check no seu config.php
    $st = $pdo->prepare("INSERT INTO Mensagem (id_remetente, id_destinatario, conteudo, data_envio) VALUES (?, ?, ?, NOW())");
    $st->execute([$id_usuario_atual, $_POST['destinatario_id'], $_POST['conteudo']]);
    
    // Redireciona de volta para a mesma conversa para mostrar a nova mensagem
    header("Location: ?chat=" . $_POST['destinatario_id']);
    exit;
}

// Pega o ID do contacto com quem estamos a conversar a partir da URL
$destinatario_id = $_GET['chat'] ?? null;

// Busca todos os outros utilizadores para a lista de contactos
$stmt_contatos = $pdo->prepare("SELECT id_usuario, nome FROM Usuario WHERE id_usuario != ?");
$stmt_contatos->execute([$id_usuario_atual]);
$contatos = $stmt_contatos->fetchAll();

// Busca o histórico de mensagens se um contacto estiver selecionado
$mensagens = [];
$nome_conversa_com = '';
if ($destinatario_id) {
    $stmt_nome = $pdo->prepare("SELECT nome FROM Usuario WHERE id_usuario = ?");
    $stmt_nome->execute([$destinatario_id]);
    $nome_conversa_com = $stmt_nome->fetchColumn();

    $stmt_mensagens = $pdo->prepare("
        SELECT * FROM Mensagem
        WHERE (id_remetente = :eu AND id_destinatario = :outro)
           OR (id_remetente = :outro AND id_destinatario = :eu)
        ORDER BY data_envio ASC
    ");
    $stmt_mensagens->execute(['eu' => $id_usuario_atual, 'outro' => $destinatario_id]);
    $mensagens = $stmt_mensagens->fetchAll();
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
      width: 350px; /* Mais largo */
      max-height: 500px; /* Mais alto */
      background: #2c2f33; /* Tema escuro */
      border: 1px solid #23272a;
      border-radius: 10px 10px 0 0;
      display: flex;
      flex-direction: column;
      font-family: Arial, sans-serif;
      color: #fff;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      z-index: 1000;
    }
    .chat-header {
      background: #23272a;
      padding: 12px;
      border-radius: 10px 10px 0 0;
      text-align: center;
      font-weight: bold;
    }
    .chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 15px;
      background: #36393f;
      display: flex;
      flex-direction: column;
    }
    .chat-message {
      margin-bottom: 10px;
      padding: 8px 12px;
      border-radius: 18px;
      max-width: 80%;
      line-height: 1.4;
      word-wrap: break-word;
    }
    .me {
      background: #007bff;
      color: #fff;
      align-self: flex-end;
      border-bottom-right-radius: 4px;
    }
    .other {
      background: #40444b; /* Fundo cinzento escuro SÓLIDO */
      color: #fff; /* Texto branco para contraste */
      align-self: flex-start;
      border-bottom-left-radius: 4px;
    }
    .chat-form {
      display: flex;
      padding: 10px;
      border-top: 1px solid #23272a;
      background: #40444b;
    }
    .chat-form input[type="text"] {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 20px;
      background-color: #33363b;
      color: white;
      margin-right: 8px;
    }
    .chat-form button {
      padding: 10px 15px;
      border: none;
      border-radius: 50%;
      background-color: #007bff;
      color: white;
      cursor: pointer;
      font-size: 16px;
    }
    .contact-list {
      padding: 10px;
      border-top: 1px solid #23272a;
      background: #2c2f33;
      max-height: 120px;
      overflow-y: auto;
    }
    .contact-list strong {
      display: block;
      text-align: center;
      margin-bottom: 5px;
      color: #aaa;
    }
    .contact-list a {
      display: block;
      padding: 8px;
      color: #00aaff;
      text-decoration: none;
      border-radius: 4px;
    }
    .contact-list a:hover, .contact-list a.active {
      background-color: #40444b;
    }
    .placeholder {
        text-align: center;
        color: #888;
        margin: auto;
    }
  </style>
</head>
<body>
  
  <div class="container" style="padding: 20px; color: #333;">
    <!-- O conteúdo da sua página fica aqui. O chat irá sobrepor-se. -->
    <a href="index.php" class="btn">Voltar à Página Inicial</a>
  </div>

  <div class="chat-box">
    <div class="chat-header">
      <?= $destinatario_id ? htmlspecialchars($nome_conversa_com) : 'Mensagens Diretas' ?>
    </div>

    <div class="chat-messages">
      <?php if ($destinatario_id): ?>
        <?php if (!empty($mensagens)): ?>
          <?php foreach ($mensagens as $msg): ?>
            <div class="chat-message <?= $msg['id_remetente'] == $id_usuario_atual ? 'me' : 'other' ?>">
              <?= htmlspecialchars($msg['conteudo']) ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
           <p class="placeholder">Ainda não há mensagens. Envie a primeira!</p>
        <?php endif; ?>
      <?php else: ?>
        <p class="placeholder">Selecione um contato na lista abaixo.</p>
      <?php endif; ?>
    </div>

    <?php if ($destinatario_id): ?>
    <form class="chat-form" method="post" action="mensagens.php?chat=<?= htmlspecialchars($destinatario_id) ?>">
      <input type="hidden" name="destinatario_id" value="<?= htmlspecialchars($destinatario_id) ?>">
      <input type="text" name="conteudo" placeholder="Digite uma mensagem..." required autocomplete="off">
      <button type="submit">➤</button>
    </form>
    <?php endif; ?>

    <div class="contact-list">
      <strong>Contatos</strong>
      <?php foreach ($contatos as $c): ?>
        <a href="?chat=<?= $c['id_usuario'] ?>" class="<?= $destinatario_id == $c['id_usuario'] ? 'active' : '' ?>">
          <?= htmlspecialchars($c['nome']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

</body>
</html>

