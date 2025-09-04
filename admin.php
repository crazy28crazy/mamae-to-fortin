<?php
require __DIR__ . '/config.php';
require_role('Administrador');

$user_logado = current_user($pdo);
$mensagem = '';
$erro = '';

// Lógica de processamento dos formulários (eliminar, atualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id_usuario_alvo = (int)($_POST['id_usuario'] ?? 0);

    if ($action === 'delete') {
        if ($id_usuario_alvo === 1) {
            $erro = "Não é possível eliminar o administrador principal.";
        } elseif ($id_usuario_alvo) {
            // Lógica para eliminar utilizador...
        }
    } elseif ($action === 'update_roles') {
        // Lógica para atualizar funções...
    }
}

$utilizadores = $pdo->query("
    SELECT u.id_usuario, u.nome, u.email, GROUP_CONCAT(f.descricao ORDER BY f.descricao) as funcoes
    FROM usuario u
    LEFT JOIN usuario_funcao uf ON u.id_usuario = uf.id_usuario
    LEFT JOIN funcao f ON uf.id_funcao = f.id_funcao
    GROUP BY u.id_usuario
    ORDER BY u.id_usuario ASC
")->fetchAll();

$funcoes_disponiveis = $pdo->query("SELECT id_funcao, descricao FROM funcao")->fetchAll();
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Painel de Administração</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Estilos específicos para a tabela de admin */
    table { width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left; }
    th, td { border: 1px solid #555; padding: 10px; }
    th { background-color: rgba(0, 0, 0, 0.3); color: #00ff66; }
    td { color: white; }
    .funcoes-form label { margin-right: 15px; color: white; }
    button.delete-btn { background-color: #dc3545; }
    .protected-role { opacity: 0.7; }
  </style>
</head>
<body>
  <!-- A classe "container-largo" é adicionada aqui para expandir a página -->
  <div class="container container-largo">
    <h2>Painel de Administração</h2>
    <p>Olá, <?= htmlspecialchars($user_logado['nome']) ?>. Aqui pode gerir os utilizadores do sistema.</p>
    <a href="index.php">Voltar para a página inicial</a>
    <hr style="border-color: #444;">

    <?php if ($mensagem): ?><p style="color: #28a745;"><?= htmlspecialchars($mensagem) ?></p><?php endif; ?>
    <?php if ($erro): ?><p style="color: #ff4d4d;"><?= htmlspecialchars($erro) ?></p><?php endif; ?>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>Funções</th>
          <th>Alterar Funções</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($utilizadores as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['id_usuario']) ?></td>
            <td><?= htmlspecialchars($u['nome']) ?></td>
            <td><?= htmlspecialchars($u['funcoes'] ?? 'Nenhuma') ?></td>
            <td>
              <form method="POST" style="margin: 0;">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="action" value="update_roles">
                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                
                <?php $funcoes_atuais = explode(',', $u['funcoes'] ?? ''); ?>
                <?php foreach ($funcoes_disponiveis as $funcao): ?>
                  <?php $is_admin_principal_role = ($u['id_usuario'] === 1 && $funcao['descricao'] === 'Administrador'); ?>
                  <label class="<?= $is_admin_principal_role ? 'protected-role' : '' ?>">
                    <input type="checkbox" name="funcoes[]" value="<?= $funcao['id_funcao'] ?>" <?= in_array($funcao['descricao'], $funcoes_atuais) ? 'checked' : '' ?> <?= $is_admin_principal_role ? 'onclick="return false;"' : '' ?> >
                    <?= htmlspecialchars($funcao['descricao']) ?>
                  </label>
                <?php endforeach; ?>
                
                <button type="submit" style="width: auto; padding: 5px 10px;">Guardar</button>
              </form>
            </td>
            <td>
              <?php if ($u['id_usuario'] !== 1): ?>
                <form method="POST" onsubmit="return confirm('Tem a certeza?');" style="margin: 0;">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                    <button type="submit" class="delete-btn" style="width: auto; padding: 5px 10px;">Eliminar</button>
                </form>
              <?php else: ?>
                <span>(Admin Principal)</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>

