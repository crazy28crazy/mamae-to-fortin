<?php
// Garante a codificação correta de caracteres
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/config.php';

$user = current_user($pdo);
$id_usuario_atual = $user['id_usuario'] ?? null;

// --- Lógica do Chat (só executa se o utilizador estiver com sessão iniciada) ---
if ($user) {
    $destinatario_id = (int)($_GET['chat'] ?? 0);

    // Marca as mensagens recebidas como lidas
    if ($destinatario_id) {
        $stmt_update = $pdo->prepare(
            "UPDATE mensagem SET data_visualizacao = NOW()
             WHERE id_destinatario = :eu AND id_remetente = :outro AND data_visualizacao IS NULL"
        );
        $stmt_update->execute(['eu' => $id_usuario_atual, 'outro' => $destinatario_id]);
    }

    // Busca contatos
    $stmt_contatos = $pdo->prepare("SELECT id_usuario, nome FROM usuario WHERE id_usuario != ? ORDER BY nome ASC");
    $stmt_contatos->execute([$id_usuario_atual]);
    $contatos = $stmt_contatos->fetchAll();

    $mensagens = [];
    $nome_conversa_com = '';
    if ($destinatario_id) {
        // Busca o nome do contato da conversa atual
        $stmt_nome = $pdo->prepare("SELECT nome FROM usuario WHERE id_usuario = ?");
        $stmt_nome->execute([$destinatario_id]);
        $nome_conversa_com = $stmt_nome->fetchColumn();

        // Busca o histórico de mensagens
        $stmt_mensagens = $pdo->prepare(
            "SELECT * FROM mensagem
             WHERE (id_remetente = :eu AND id_destinatario = :outro) OR (id_remetente = :outro AND id_destinatario = :eu)
             ORDER BY data_envio ASC"
        );
        $stmt_mensagens->execute(['eu' => $id_usuario_atual, 'outro' => $destinatario_id]);
        $mensagens = $stmt_mensagens->fetchAll();
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Mamãe to fortin - Início</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Estilos Gerais */
    h1, p { color: white; }
    /* Estilos da Caixa de Chat */
    .chat-box { position: fixed; right: 20px; bottom: 0; width: 350px; max-height: 500px; background: #2c2f33; border: 1px solid #23272a; border-radius: 10px 10px 0 0; display: flex; flex-direction: column; font-family: Arial, sans-serif; color: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.3); z-index: 1000; }
    .chat-header { background: #23272a; padding: 12px; border-radius: 10px 10px 0 0; text-align: center; font-weight: bold; }
    /* Estilos da Área de Mensagens */
    .chat-messages { flex: 1; overflow-y: auto; padding: 15px; background: #36393f; display: flex; flex-direction: column; }
    .message-wrapper { display: flex; flex-direction: column; margin-bottom: 5px; }
    .me { align-items: flex-end; }
    .other { align-items: flex-start; }
    .chat-message { padding: 8px 12px; border-radius: 18px; max-width: 85%; line-height: 1.4; word-wrap: break-word; position: relative; }
    .me .chat-message { background: #005C4B; color: #fff; border-bottom-right-radius: 4px; }
    .other .chat-message { background: #40444b; color: #fff; border-bottom-left-radius: 4px; }
    .message-meta { font-size: 0.75em; color: #a0a0a0; text-align: right; margin-top: 5px; }
    .status-ticks { display: inline-block; margin-left: 5px; font-weight: bold; }
    .status-ticks.seen { color: #53bdeb; }
    /* Estilos do Formulário */
    .chat-form { display: flex; align-items: center; padding: 10px; border-top: 1px solid #23272a; background: #40444b; }
    .chat-form input[type="text"] { flex: 1; height: 40px; padding: 0 15px; border: none; border-radius: 20px; background-color: #33363b; color: white; margin-right: 8px; }
    .chat-form button { width: 40px; height: 40px; border: none; border-radius: 50%; background-color: #007bff; color: white; cursor: pointer; font-size: 18px; display: flex; justify-content: center; align-items: center; }
    /* Estilos da Lista de Contatos */
    .contact-list { padding: 10px; border-top: 1px solid #23272a; background: #2c2f33; max-height: 120px; overflow-y: auto; }
    .contact-list strong { display: block; text-align: center; margin-bottom: 5px; color: #aaa; }
    .contact-list a { display: block; padding: 8px; color: #00aaff; text-decoration: none; border-radius: 4px; }
    .contact-list a:hover, .contact-list a.active { background-color: #40444b; }
    .placeholder { text-align: center; color: #888; margin: auto; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Bem-vindo à mamãe to fortin</h1>
    <p>Gerencie os seus treinos, planos e agendamentos de forma simples.</p>
    <nav>
      <?php if ($user): ?>
        <a href="perfil.php">Perfil</a> |
        <a href="planos.php">Planos</a> |
        <a href="agendamento.php">Agendamento</a> |
        <?php if (is_admin($user)): ?>
            <a href="admin.php">Painel Admin</a> |
        <?php endif; ?>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a> |
        <a href="cadastro.php">Cadastro</a>
      <?php endif; ?>
    </nav>
  </div>

  <?php if ($user): ?>
  <div class="chat-box">
    <div class="chat-header">
      <?= $destinatario_id ? htmlspecialchars($nome_conversa_com) : 'Mensagens Diretas' ?>
    </div>
    <div class="chat-messages">
      <?php if ($destinatario_id && !empty($mensagens)): ?>
        <?php foreach ($mensagens as $msg): ?>
          <div class="message-wrapper <?= $msg['id_remetente'] == $id_usuario_atual ? 'me' : 'other' ?>">
            <div class="chat-message">
              <?= htmlspecialchars($msg['conteudo']) ?>
            </div>
            <div class="message-meta">
              <span><?= date('H:i', strtotime($msg['data_envio'])) ?></span>
              <?php if ($msg['id_remetente'] == $id_usuario_atual): ?>
                <span class="status-ticks <?= $msg['data_visualizacao'] ? 'seen' : '' ?>">✓✓</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="placeholder">Selecione um contato na lista abaixo.</p>
      <?php endif; ?>
    </div>

    <div class="contact-list">
      <strong>Contatos</strong>
      <?php foreach ($contatos as $c): ?>
        <a href="index.php?chat=<?= $c['id_usuario'] ?>" class="<?= $destinatario_id == $c['id_usuario'] ? 'active' : '' ?>">
          <?= htmlspecialchars($c['nome']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($destinatario_id): ?>
    <form class="chat-form" method="post" action="chat.php">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="destinatario_id" value="<?= htmlspecialchars($destinatario_id) ?>">
      <input type="text" name="conteudo" placeholder="Digite uma mensagem..." required autocomplete="off">
      <button type="submit">➤</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</body>
</html>

