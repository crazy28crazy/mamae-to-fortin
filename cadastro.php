<?php
require __DIR__ . '/config.php';

// Se o utilizador já tem sessão iniciada, redireciona para a página inicial
if (current_user($pdo)) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    // Recolha e validação dos dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $idade = (int)($_POST['idade'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo_usuario = $_POST['tipo_usuario'] ?? 'aluno'; // O valor vem do input hidden
    $cref = ($tipo_usuario === 'personal') ? trim($_POST['cref'] ?? '') : null;

    if (empty($nome) || empty($email) || empty($senha) || empty($tipo_usuario)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif ($tipo_usuario === 'personal' && empty($cref)) {
        $erro = "O campo CREF é obrigatório para personal trainers.";
    } else {
        $pdo->beginTransaction();
        try {
            // A lógica de inserção na base de dados continua igual
            $stmt_user = $pdo->prepare("INSERT INTO usuario (nome, cpf, idade, email, senha) VALUES (?, ?, ?, ?, ?)");
            $stmt_user->execute([$nome, $cpf, $idade, $email, password_hash($senha, PASSWORD_DEFAULT)]);
            $id_novo_usuario = $pdo->lastInsertId();

            $id_funcao_aluno = 1;
            $id_funcao_personal = 2;
            
            $stmt_funcao = $pdo->prepare("INSERT INTO usuario_funcao (id_usuario, id_funcao) VALUES (?, ?)");
            
            if ($tipo_usuario === 'aluno') {
                $stmt_funcao->execute([$id_novo_usuario, $id_funcao_aluno]);
            } elseif ($tipo_usuario === 'personal') {
                $stmt_funcao->execute([$id_novo_usuario, $id_funcao_personal]);
                
                $stmt_personal = $pdo->prepare("INSERT INTO personal_trainer (id_usuario, cref) VALUES (?, ?)");
                $stmt_personal->execute([$id_novo_usuario, $cref]);
                
                $total_personais = $pdo->query("SELECT COUNT(*) FROM personal_trainer")->fetchColumn();
                if ($total_personais == 1) {
                    $id_plano_gold = $pdo->query("SELECT id_plano FROM plano WHERE nome_plano = 'Plano Gold'")->fetchColumn();
                    if ($id_plano_gold) {
                        $stmt_plano = $pdo->prepare("INSERT INTO pagamento (id_usuario, id_plano, data_pagamento, status, id_transacao_api) VALUES (?, ?, CURDATE(), 'pago', ?)");
                        $stmt_plano->execute([$id_novo_usuario, $id_plano_gold, 'Primeiro Personal - Boas-Vindas']);
                    }
                }
            }
            
            $admins = $pdo->query("SELECT COUNT(*) FROM usuario_funcao WHERE id_funcao = 3")->fetchColumn();
            if ($admins == 0) {
                $stmt_funcao->execute([$id_novo_usuario, 3]);
            }

            $pdo->commit();
            header("Location: login.php?registo=sucesso");
            exit;

        } catch (Throwable $e) {
            $pdo->rollBack();
            if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
                 $erro = "Este e-mail já está a ser utilizado. Por favor, escolha outro.";
            } else {
                 $erro = "Ocorreu um erro ao criar a sua conta. Tente novamente.";
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
  <style>
    /* Estilos para o interruptor (toggle switch) */
    .switch-container {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 20px 0;
        gap: 15px;
        color: white;
    }
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #555;
        transition: .4s;
        border-radius: 34px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider {
        background-color: #00ff66; /* Cor verde quando ativo */
    }
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    #campo-cref {
        transition: all 0.4s ease;
        overflow: hidden;
        max-height: 0;
    }
    #campo-cref.visible {
        max-height: 100px; /* Altura suficiente para mostrar o campo */
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Crie a sua Conta</h2>
    <?php if ($erro): ?>
      <p style="color: #ff4d4d;"><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>
    <form method="post" id="cadastro-form">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="text" name="nome" placeholder="Nome Completo" required>
      <input type="text" name="cpf" placeholder="CPF">
      <input type="number" name="idade" placeholder="Idade">
      <input type="email" name="email" placeholder="E-mail" required>
      <input type="password" name="senha" placeholder="Senha" required>

      <!-- Novo interruptor -->
      <div class="switch-container">
          <span>Aluno</span>
          <label class="switch">
              <input type="checkbox" id="tipo_usuario_switch">
              <span class="slider"></span>
          </label>
          <span>Personal Trainer</span>
      </div>
      
      <!-- Input hidden que envia o valor para o PHP -->
      <input type="hidden" name="tipo_usuario" id="tipo_usuario_hidden" value="aluno">

      <div id="campo-cref">
          <input type="text" name="cref" placeholder="CREF (ex: 123456-G/SP)">
      </div>

      <button type="submit">Cadastrar</button>
    </form>
    <p>Já tem conta? <a href="login.php">Faça login</a></p>
  </div>
  
  <script>
    const tipoUsuarioSwitch = document.getElementById('tipo_usuario_switch');
    const tipoUsuarioHidden = document.getElementById('tipo_usuario_hidden');
    const campoCref = document.getElementById('campo-cref');

    tipoUsuarioSwitch.addEventListener('change', function() {
        if (this.checked) {
            // Se estiver marcado (direita), é Personal Trainer
            tipoUsuarioHidden.value = 'personal';
            campoCref.classList.add('visible');
        } else {
            // Se não estiver marcado (esquerda), é Aluno
            tipoUsuarioHidden.value = 'aluno';
            campoCref.classList.remove('visible');
        }
    });
  </script>
</body>
</html>

