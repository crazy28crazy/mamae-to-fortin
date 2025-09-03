<?php
require __DIR__ . '/config.php';
require_role('PersonalTrainer');

$user = current_user($pdo);
$aluno_id = (int)($_GET['aluno_id'] ?? 0);
$mensagem = '';

// Busca o id_personal do utilizador logado
$stmt_personal_id = $pdo->prepare("SELECT id_personal FROM personal_trainer WHERE id_usuario = ?");
$stmt_personal_id->execute([$user['id_usuario']]);
$id_personal = $stmt_personal_id->fetchColumn();

if (!$aluno_id || !$id_personal) {
    header("Location: gestao_alunos.php"); exit;
}

// Busca o nome do aluno para o título
$stmt_aluno = $pdo->prepare("SELECT nome FROM usuario WHERE id_usuario = ?");
$stmt_aluno->execute([$aluno_id]);
$aluno = $stmt_aluno->fetch();

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $dados = [
        'objetivos' => $_POST['objetivos'] ?? '',
        'historico_lesoes' => $_POST['historico_lesoes'] ?? '',
        'medicamentos' => $_POST['medicamentos'] ?? '',
        'observacoes' => $_POST['observacoes'] ?? ''
    ];

    $stmt_check = $pdo->prepare("SELECT id_anamnese FROM anamnese WHERE id_aluno = ? AND id_personal = ?");
    $stmt_check->execute([$aluno_id, $id_personal]);
    
    if ($stmt_check->fetch()) { // Se existe, atualiza
        $stmt_update = $pdo->prepare("UPDATE anamnese SET objetivos = :objetivos, historico_lesoes = :historico_lesoes, medicamentos = :medicamentos, observacoes = :observacoes WHERE id_aluno = :id_aluno AND id_personal = :id_personal");
        $stmt_update->execute(array_merge($dados, ['id_aluno' => $aluno_id, 'id_personal' => $id_personal]));
    } else { // Se não existe, insere
        $stmt_insert = $pdo->prepare("INSERT INTO anamnese (id_aluno, id_personal, objetivos, historico_lesoes, medicamentos, observacoes) VALUES (:id_aluno, :id_personal, :objetivos, :historico_lesoes, :medicamentos, :observacoes)");
        $stmt_insert->execute(array_merge($dados, ['id_aluno' => $aluno_id, 'id_personal' => $id_personal]));
    }
    $mensagem = "Anamnese guardada com sucesso!";
}

// Busca os dados da anamnese (se existirem) para preencher o formulário
$stmt_dados = $pdo->prepare("SELECT * FROM anamnese WHERE id_aluno = ? AND id_personal = ?");
$stmt_dados->execute([$aluno_id, $id_personal]);
$anamnese = $stmt_dados->fetch() ?: []; // Garante que não é nulo
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Anamnese de <?= htmlspecialchars($aluno['nome']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style> body, .container { color: white; } textarea { width: 100%; min-height: 80px; } </style>
</head>
<body>
  <div class="container">
    <h2>Ficha de Anamnese - <?= htmlspecialchars($aluno['nome']) ?></h2>
    <a href="gestao_alunos.php">Voltar para a lista de alunos</a>
    <hr>
    <?php if ($mensagem): ?>
        <p style="color: #28a745;"><?= htmlspecialchars($mensagem) ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        
        <label for="objetivos">Principais Objetivos:</label><br>
        <textarea name="objetivos" id="objetivos"><?= htmlspecialchars($anamnese['objetivos'] ?? '') ?></textarea><br><br>
        
        <label for="historico_lesoes">Histórico de Lesões ou Cirurgias:</label><br>
        <textarea name="historico_lesoes" id="historico_lesoes"><?= htmlspecialchars($anamnese['historico_lesoes'] ?? '') ?></textarea><br><br>
        
        <label for="medicamentos">Uso de Medicamentos Contínuos:</label><br>
        <textarea name="medicamentos" id="medicamentos"><?= htmlspecialchars($anamnese['medicamentos'] ?? '') ?></textarea><br><br>
        
        <label for="observacoes">Observações Adicionais:</label><br>
        <textarea name="observacoes" id="observacoes"><?= htmlspecialchars($anamnese['observacoes'] ?? '') ?></textarea><br><br>

        <button type="submit" class="btn">Guardar Anamnese</button>
    </form>
  </div>
</body>
</html>
