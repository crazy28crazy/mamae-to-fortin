<?php
require __DIR__ . '/config.php';
require_role('Administrador'); // Exige que o utilizador seja administrador para aceder

$user_logado = current_user($pdo);
$mensagem = '';

// Processa o formulário de alteração de funções
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id_usuario_alvo = (int)($_POST['id_usuario'] ?? 0);
    $funcoes_selecionadas_ids = $_POST['funcoes'] ?? [];

    // Busca todas as funções existentes para validar
    $todas_funcoes = $pdo->query("SELECT id_funcao, descricao FROM funcao")->fetchAll(PDO::FETCH_KEY_PAIR);
    $id_funcao_personal = array_search('PersonalTrainer', $todas_funcoes);

    if ($id_usuario_alvo) {
        $pdo->beginTransaction();
        try {
            // 1. Remove todas as funções atuais do utilizador
            $stmt_delete = $pdo->prepare("DELETE FROM usuario_funcao WHERE id_usuario = ?");
            $stmt_delete->execute([$id_usuario_alvo]);

            // 2. Insere as novas funções selecionadas
            $stmt_insert = $pdo->prepare("INSERT INTO usuario_funcao (id_usuario, id_funcao) VALUES (?, ?)");
            foreach ($funcoes_selecionadas_ids as $id_funcao) {
                if (array_key_exists($id_funcao, $todas_funcoes)) {
                    $stmt_insert->execute([$id_usuario_alvo, $id_funcao]);
                }
            }

            // 3. Sincroniza com a tabela personal_trainer
            $stmt_check_personal = $pdo->prepare("SELECT id_personal FROM personal_trainer WHERE id_usuario = ?");
            $stmt_check_personal->execute([$id_usuario_alvo]);
            $is_already_personal = $stmt_check_personal->fetch();

            // Se a função PersonalTrainer foi selecionada e ele não está na tabela, insere.
            if (in_array($id_funcao_personal, $funcoes_selecionadas_ids) && !$is_already_personal) {
                $stmt_add_personal = $pdo->prepare("INSERT INTO personal_trainer (id_usuario) VALUES (?)");
                $stmt_add_personal->execute([$id_usuario_alvo]);
            } 
            // Se a função não foi selecionada e ele está na tabela, remove.
            elseif (!in_array($id_funcao_personal, $funcoes_selecionadas_ids) && $is_already_personal) {
                $stmt_remove_personal = $pdo->prepare("DELETE FROM personal_trainer WHERE id_usuario = ?");
                $stmt_remove_personal->execute([$id_usuario_alvo]);
            }

            $pdo->commit();
            $mensagem = "Funções do utilizador atualizadas com sucesso!";
        } catch (Throwable $e) {
            $pdo->rollBack();
            $mensagem = "Erro ao atualizar as funções.";
            // Para depuração: error_log($e->getMessage());
        }
    }
}


// Busca todos os utilizadores e as suas funções
$utilizadores = $pdo->query("
    SELECT u.id_usuario, u.nome, u.email, GROUP_CONCAT(f.descricao) as funcoes
    FROM usuario u
    LEFT JOIN usuario_funcao uf ON u.id_usuario = uf.id_usuario
    LEFT JOIN funcao f ON uf.id_funcao = f.id_funcao
    GROUP BY u.id_usuario
    ORDER BY u.nome ASC
")->fetchAll();

// Busca todas as funções disponíveis para o formulário
$funcoes_disponiveis = $pdo->query("SELECT id_funcao, descricao FROM funcao")->fetchAll();

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Painel de Administração</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Define a cor padrão do texto para branco */
    body, .container {
        color: white;
    }
    /* Estilo para a tabela com tema escuro */
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 20px; 
        color: white; /* Garante que o texto na tabela seja branco */
    }
    th, td { 
        border: 1px solid #555; /* Borda mais escura */
        padding: 8px; 
        text-align: left; 
    }
    th { 
        background-color: #333; /* Fundo do cabeçalho escuro */
    }
    /* Estilo para links para que fiquem visíveis */
    a {
        color: #87CEFA; /* Azul claro */
    }
    /* Estilo para os checkboxes e texto */
    .funcoes-form label { 
        margin-right: 15px; 
        color: white; /* Garante que o texto dos labels seja branco */
    }
    /* Estilo para o botão */
    button {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
    }
    button:hover {
        background-color: #0056b3;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Painel de Administração - Gestão de Funções</h2>
    <p>Olá, <?= htmlspecialchars($user_logado['nome']) ?>. Aqui pode atribuir ou remover funções dos utilizadores.</p>
    <a href="index.php">Voltar para a página inicial</a>
    <hr>

    <?php if ($mensagem): ?>
        <p style="color: #28a745; font-weight: bold;"><?= htmlspecialchars($mensagem) ?></p>
    <?php endif; ?>

    <table>
      <thead>
        <tr>
          <th>Nome</th>
          <th>Email</th>
          <th>Funções Atuais</th>
          <th>Alterar Funções</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($utilizadores as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['nome']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['funcoes'] ?? 'Nenhuma') ?></td>
            <td>
              <form method="POST" class="funcoes-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                
                <?php
                $funcoes_atuais = explode(',', $u['funcoes'] ?? '');
                foreach ($funcoes_disponiveis as $funcao):
                ?>
                  <label>
                    <input type="checkbox" name="funcoes[]" value="<?= $funcao['id_funcao'] ?>" 
                           <?= in_array($funcao['descricao'], $funcoes_atuais) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($funcao['descricao']) ?>
                  </label>
                <?php endforeach; ?>
                
                <button type="submit">Guardar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>

