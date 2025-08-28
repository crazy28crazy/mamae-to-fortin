<?php
require __DIR__ . '/config.php';

// A verificação de login foi movida para depois da inclusão do config,
// mas a lógica principal da página só executa se o usuário estiver logado.
$usuario_logado = !empty($_SESSION['user_id']);

if ($usuario_logado) {
    $user = current_user($pdo);

    // Busca mensagens do usuário (somente onde ele é remetente ou destinatário)
    $st = $pdo->prepare("
        SELECT m.*, u.nome AS remetente_nome
        FROM Mensagem m
        JOIN Usuario u ON m.id_remetente = u.id_usuario
        WHERE m.id_remetente = :id OR m.id_destinatario = :id
        ORDER BY m.data_envio ASC
    ");
    $st->execute(['id' => $user['id_usuario']]);
    $mensagens = $st->fetchAll(PDO::FETCH_ASSOC);

    // Busca outros usuários para o chat
    $st_users = $pdo->prepare("SELECT id_usuario, nome FROM Usuario WHERE id_usuario != :id");
    $st_users->execute(['id' => $user['id_usuario']]);
    $usuarios = $st_users->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Mamãe to fortin - Início</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <h1 style="color: white;">Bem-vindo à mamãe to fortin</h1>
    <p style="color: white;">Gerencie seus treinos, planos e agendamentos de forma simples.</p>
    <nav>
      <!-- Lógica de Navegação Atualizada -->
      <?php if ($usuario_logado): ?>
        <a href="perfil.php">Perfil</a> |
        <a href="planos.php">Planos</a> |
        <a href="agendamento.php">Agendamento</a> |
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a> |
        <a href="cadastro.php">Cadastro</a> |
        <a href="planos.php">Planos</a>
      <?php endif; ?>
    </nav>
  </div>

  <!-- A caixa de chat só aparece se o usuário estiver logado -->
  <?php if ($usuario_logado): ?>
  <div class="chat-box">
    <div class="chat-header">Chat</div>
    <div class="chat-messages">
      <?php if (empty($mensagens)): ?>
        <p class="sem-msg">Nenhuma mensagem ainda.</p>
      <?php else: ?>
        <?php foreach ($mensagens as $m): ?>
          <div class="chat-message <?= $m['id_remetente'] == $user['id_usuario'] ? 'sent' : 'received' ?>">
            <p><strong><?= htmlspecialchars($m['remetente_nome']) ?>:</strong> <?= htmlspecialchars($m['conteudo']) ?></p>
            <small><?= date("d/m H:i", strtotime($m['data_envio'])) ?></small>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Formulário do chat -->
    <form class="chat-form" method="post" action="chat.php">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <!-- Selecionar destinatário -->
      <select name="destinatario_id" required>
        <option value="">-- Escolha o usuário --</option>
        <?php foreach ($usuarios as $u): ?>
          <option value="<?= $u['id_usuario'] ?>"><?= htmlspecialchars($u['nome']) ?></option>
        <?php endforeach; ?>
      </select>

      <input type="text" name="conteudo" placeholder="Digite uma mensagem..." required>
      <button type="submit">✈</button>
    </form>
  </div>
  <?php endif; ?>
</body>
</html>
