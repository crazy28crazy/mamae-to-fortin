<?php
require __DIR__ . '/config.php';

// Se o utilizador já tem sessão iniciada, redireciona para a página inicial.
if (current_user($pdo)) {
    header("Location: index.php");
    exit;
}

$erro = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validação CSRF
    csrf_check();

    $nome = trim($_POST["nome"] ?? '');
    $cpf = trim($_POST["cpf"] ?? '');
    $idade = (int)($_POST["idade"] ?? 0);
    $email = trim($_POST["email"] ?? '');
    $senha = $_POST["senha"] ?? '';

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha) || empty($cpf)) {
        $erro = "Todos os campos obrigatórios devem ser preenchidos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "O formato do e-mail é inválido.";
    } else {
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

        $pdo->beginTransaction();
        try {
            // 1. Insere o novo utilizador na tabela 'usuario'
            $stmt_user = $pdo->prepare("INSERT INTO usuario (nome, cpf, idade, email, senha) VALUES (?, ?, ?, ?, ?)");
            $stmt_user->execute([$nome, $cpf, $idade, $email, $senha_hash]);
            $id_novo_usuario = $pdo->lastInsertId();

            // 2. Atribui a função de Aluno (padrão para todos)
            $id_funcao_aluno = $pdo->query("SELECT id_funcao FROM funcao WHERE descricao = 'Aluno'")->fetchColumn();
            if ($id_funcao_aluno) {
                $stmt_funcao = $pdo->prepare("INSERT INTO usuario_funcao (id_usuario, id_funcao) VALUES (?, ?)");
                $stmt_funcao->execute([$id_novo_usuario, $id_funcao_aluno]);
            }

            // 3. Verifica se já existe algum administrador no sistema
            $id_funcao_admin = $pdo->query("SELECT id_funcao FROM funcao WHERE descricao = 'Administrador'")->fetchColumn();
            if ($id_funcao_admin) {
                $stmt_check_admin = $pdo->prepare("SELECT COUNT(*) FROM usuario_funcao WHERE id_funcao = ?");
                $stmt_check_admin->execute([$id_funcao_admin]);
                $admin_count = $stmt_check_admin->fetchColumn();

                // Se não houver administradores, torna este novo utilizador um administrador
                if ($admin_count == 0) {
                    $stmt_funcao->execute([$id_novo_usuario, $id_funcao_admin]);
                }
            }
            
            $pdo->commit();
            header("Location: login.php");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            // Verifica se o erro é de duplicidade de e-mail ou CPF
            if ($e->getCode() == 23000) {
                $erro = "O e-mail ou CPF informado já está em uso.";
            } else {
                $erro = "Erro ao efetuar o registo. Tente novamente.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Cadastro</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
      <h2>Cadastro</h2>
      <?php if ($erro): ?>
          <p style="color: #ff4d4d;"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <form method="post">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="text" name="nome" placeholder="Nome Completo" required><br><br>
          <input type="text" name="cpf" placeholder="CPF (apenas números)" required><br><br>
          <input type="number" name="idade" placeholder="Idade"><br><br>
          <input type="email" name="email" placeholder="E-mail" required><br><br>
          <input type="password" name="senha" placeholder="Senha" required><br><br>
          <button type="submit" class="btn">Cadastrar</button>
      </form>
      <p><a href="login.php" class="btn">Já tenho uma conta</a></p>
  </div>
</body>
</html>

