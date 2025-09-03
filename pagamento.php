<?php
require __DIR__ . '/config.php';
require_role('PersonalTrainer');

$user = current_user($pdo);
$id_plano = (int)($_POST['id_plano'] ?? 0);
$token = $_POST['stripeToken'] ?? null;
$mensagem = '';
$sucesso = false;

if ($token && $id_plano) {
    csrf_check();
    
    // --- SIMULAÇÃO DA API STRIPE ---
    // No mundo real, aqui você usaria a biblioteca da Stripe para PHP
    // require_once('vendor/autoload.php');
    // \Stripe\Stripe::setApiKey('SUA_CHAVE_SECRETA');
    // try {
    //   $charge = \Stripe\Charge::create([...]);
    //   $id_transacao_api = $charge->id;
    //   $sucesso = true;
    // } catch (\Stripe\Exception\CardException $e) { ... }
    
    // Como é uma simulação, vamos apenas assumir que foi um sucesso.
    $id_transacao_api = 'sim_charge_' . bin2hex(random_bytes(10));
    $sucesso = true;
    // --- FIM DA SIMULAÇÃO ---

    if ($sucesso) {
        $stmt = $pdo->prepare(
            "INSERT INTO pagamento (id_usuario, id_plano, data_pagamento, status, id_transacao_api) VALUES (?, ?, CURDATE(), ?, ?)"
        );
        $stmt->execute([$user['id_usuario'], $id_plano, 'Aprovado', $id_transacao_api]);
        $mensagem = "Pagamento aprovado com sucesso! O seu plano está ativo.";
    } else {
        $mensagem = "Ocorreu um erro ao processar o seu pagamento. Tente novamente.";
    }
} else {
    header("Location: planos.php");
    exit;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Status do Pagamento</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style> body, .container { color: white; } </style>
</head>
<body>
    <div class="container">
        <h2><?= $sucesso ? 'Obrigado!' : 'Erro no Pagamento' ?></h2>
        <p><?= htmlspecialchars($mensagem) ?></p>
        <br>
        <a href="index.php" class="btn">Voltar à Página Inicial</a>
    </div>
</body>
</html>
