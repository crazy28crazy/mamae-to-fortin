<?php
// Usa o config.php para conexão e funções.
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST["nome"];
    $cpf = $_POST["cpf"];
    $idade = $_POST["idade"];
    $email = $_POST["email"];
    $senha = password_hash($_POST["senha"], PASSWORD_BCRYPT);

    // Usa uma transação para garantir que o usuário e sua função sejam criados juntos.
    $pdo->beginTransaction();

    try {
        // 1. Insere o novo usuário na tabela Usuario.
        $stmt = $pdo->prepare("INSERT INTO Usuario (nome, cpf, idade, email, senha) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $cpf, $idade, $email, $senha]);
        $id_novo_usuario = $pdo->lastInsertId();

        // 2. Busca o ID da função "Aluno" no banco.
        $stmt_funcao = $pdo->prepare("SELECT id_funcao FROM Funcao WHERE descricao = 'Aluno'");
        $stmt_funcao->execute();
        $id_funcao_aluno = $stmt_funcao->fetchColumn();

        if (!$id_funcao_aluno) {
            throw new Exception("A função 'Aluno' não foi encontrada no banco de dados.");
        }

        // 3. Associa o novo usuário à função "Aluno".
        $stmt_assoc = $pdo->prepare("INSERT INTO Usuario_Funcao (id_usuario, id_funcao) VALUES (?, ?)");
        $stmt_assoc->execute([$id_novo_usuario, $id_funcao_aluno]);

        // Confirma as alterações no banco de dados.
        $pdo->commit();

        header("Location: login.php?cadastro=sucesso");
        exit;

    } catch (Exception $e) {
        // Se algo der errado, desfaz tudo.
        $pdo->rollBack();
        $erro = "Erro ao cadastrar: " . $e->getMessage();
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
      <?php if (!empty($erro)): ?>
          <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <form method="post">
          <input type="text" name="nome" placeholder="Nome" required><br><br>
          <input type="text" name="cpf" placeholder="CPF" required><br><br>
          <input type="number" name="idade" placeholder="Idade"><br><br>
          <input type="email" name="email" placeholder="E-mail" required><br><br>
          <input type="password" name="senha" placeholder="Senha" required><br><br>
          <button type="submit" class="btn">Cadastrar</button>
      </form>
      <p><a href="login.php" class="btn">Já tenho conta</a></p>
  </div>
</body>
</html>
