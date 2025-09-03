<?php
require __DIR__ . '/config.php';
require_role('Administrador'); // Exige que o utilizador seja administrador para aceder

$user_logado = current_user($pdo);
$mensagem = '';
$erro = '';

// --- LÓGICA DE PROCESSAMENTO DOS FORMULÁRIOS ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id_usuario_alvo = (int)($_POST['id_usuario'] ?? 0);

    // --- AÇÃO: ELIMINAR UTILIZADOR ---
    if ($action === 'delete') {
        if ($id_usuario_alvo === 1) {
            $erro = "Não é possível eliminar o administrador principal do sistema.";
        } elseif ($id_usuario_alvo) {
            $pdo->beginTransaction();
            try {
                // Remove dependências em outras tabelas
                $pdo->prepare("DELETE FROM usuario_funcao WHERE id_usuario = ?")->execute([$id_usuario_alvo]);
                $pdo->prepare("DELETE FROM personal_trainer WHERE id_usuario = ?")->execute([$id_usuario_alvo]);
                $pdo->prepare("DELETE FROM mensagem WHERE id_remetente = ? OR id_destinatario = ?")->execute([$id_usuario_alvo, $id_usuario_alvo]);
                $pdo->prepare("DELETE FROM agendamento WHERE id_usuario = ?")->execute([$id_usuario_alvo]);
                $pdo->prepare("DELETE FROM pagamento WHERE id_usuario = ?")->execute([$id_usuario_alvo]);
                
                // Remove o utilizador principal
                $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?")->execute([$id_usuario_alvo]);
                
                $pdo->commit();
                $mensagem = "Utilizador eliminado com sucesso!";
            } catch (Throwable $e) {
                $pdo->rollBack();
                $erro = "Erro ao eliminar o utilizador.";
            }
        }
    }
    // --- AÇÃO: ATUALIZAR FUNÇÕES ---
    elseif ($action === 'update_roles') {
        $funcoes_selecionadas_ids = $_POST['funcoes'] ?? [];
        $todas_funcoes = $pdo->query("SELECT id_funcao, descricao FROM funcao")->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Proteção: Garante que o admin principal (ID 1) nunca perca a sua função de Administrador
        if ($id_usuario_alvo === 1) {
            $id_funcao_admin = array_search('Administrador', $todas_funcoes);
            if ($id_funcao_admin && !in_array($id_funcao_admin, $funcoes_selecionadas_ids)) {
                $funcoes_selecionadas_ids[] = $id_funcao_admin; // Adiciona a função de volta se for removida
            }
        }
        
        if ($id_usuario_alvo) {
            // A lógica para atualizar funções e sincronizar com personal_trainer continua aqui
            $id_funcao_personal = array_search('PersonalTrainer', $todas_funcoes);
            $pdo->beginTransaction();
            try {
                $pdo->prepare("DELETE FROM usuario_funcao WHERE id_usuario = ?")->execute([$id_usuario_alvo]);
                $stmt_insert = $pdo->prepare("INSERT INTO usuario_funcao (id_usuario, id_funcao) VALUES (?, ?)");
                foreach ($funcoes_selecionadas_ids as $id_funcao) {
                    if (array_key_exists($id_funcao, $todas_funcoes)) {
                        $stmt_insert->execute([$id_usuario_alvo, $id_funcao]);
                    }
                }
                
                $is_already_personal = $pdo->prepare("SELECT 1 FROM personal_trainer WHERE id_usuario = ?");
                $is_already_personal->execute([$id_usuario_alvo]);
                $is_already_personal = $is_already_personal->fetch();

                if (in_array($id_funcao_personal, $funcoes_selecionadas_ids) && !$is_already_personal) {
                    $pdo->prepare("INSERT INTO personal_trainer (id_usuario) VALUES (?)")->execute([$id_usuario_alvo]);
                } elseif (!in_array($id_funcao_personal, $funcoes_selecionadas_ids) && $is_already_personal) {
                    $pdo->prepare("DELETE FROM personal_trainer WHERE id_usuario = ?")->execute([$id_usuario_alvo]);
                }

                $pdo->commit();
                $mensagem = "Funções atualizadas com sucesso!";
            } catch (Throwable $e) {
                $pdo->rollBack();
                $erro = "Erro ao atualizar as funções.";
            }
        }
    }
}


// --- LÓGICA DE BUSCA DE DADOS PARA EXIBIÇÃO ---
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
    body, .container { color: white; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #555; padding: 10px; text-align: left; }
    th { background-color: #333; }
    a { color: #87CEFA; }
    .funcoes-form label { margin-right: 15px; }
    button { background-color: #007bff; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
    button.delete-btn { background-color: #dc3545; }
    .protected-role { opacity: 0.7; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Painel de Administração</h2>
    <p>Olá, <?= htmlspecialchars($user_logado['nome']) ?>. Aqui pode gerir os utilizadores do sistema.</p>
    <a href="index.php">Voltar para a página inicial</a>
    <hr>

    <?php if ($mensagem): ?>
        <p style="color: #28a745; font-weight: bold;"><?= htmlspecialchars($mensagem) ?></p>
    <?php endif; ?>
    <?php if ($erro): ?>
        <p style="color: #ff4d4d; font-weight: bold;"><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>

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
              <form method="POST">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="action" value="update_roles">
                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                
                <?php $funcoes_atuais = explode(',', $u['funcoes'] ?? ''); ?>
                <?php foreach ($funcoes_disponiveis as $funcao): ?>
                  <?php $is_admin_principal_role = ($u['id_usuario'] === 1 && $funcao['descricao'] === 'Administrador'); ?>
                  <label class="<?= $is_admin_principal_role ? 'protected-role' : '' ?>">
                    <input 
                      type="checkbox" 
                      name="funcoes[]" 
                      value="<?= $funcao['id_funcao'] ?>" 
                      <?= in_array($funcao['descricao'], $funcoes_atuais) ? 'checked' : '' ?>
                      <?= $is_admin_principal_role ? 'onclick="return false;"' : '' ?> 
                    >
                    <?= htmlspecialchars($funcao['descricao']) ?>
                  </label>
                <?php endforeach; ?>
                
                <button type="submit">Guardar</button>
              </form>
            </td>
            <td>
              <?php if ($u['id_usuario'] !== 1): ?>
                <form method="POST" onsubmit="return confirm('Tem a certeza que deseja eliminar este utilizador? Esta ação é irreversível.');">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                    <button type="submit" class="delete-btn">Eliminar</button>
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

