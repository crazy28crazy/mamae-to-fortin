<?php
require __DIR__ . '/config.php';
require_role('PersonalTrainer');

// Carrega a biblioteca da Stripe instalada via Composer
require_once 'vendor/autoload.php';

$user = current_user($pdo);
$id_plano = (int)($_POST['id_plano'] ?? 0);
$token = $_POST['stripeToken'] ?? null;
$erro = '';

// Validação inicial
if (!$token || !$id_plano) {
    header("Location: planos.php");
    exit;
}

csrf_check();

// Busca os detalhes do plano no banco de dados
$stmt = $pdo->prepare("SELECT * FROM plano WHERE id_plano = ?");
$stmt->execute([$id_plano]);
$plano = $stmt->fetch();

if (!$plano) {
    header('Location: planos.php');
    exit;
}

// --- IMPLEMENTAÇÃO REAL DA API STRIPE ---
// Substitua pela sua chave secreta da Stripe
\Stripe\Stripe::setApiKey('SUA_CHAVE_SECRETA_REAL_AQUI');

try {
    // Tenta criar a cobrança na API da Stripe
    $charge = \Stripe\Charge::create([
        'amount' => $plano['preco'] * 100, // O valor deve ser em cêntimos
        'currency' => 'brl', // Moeda: Real Brasileiro
        'description' => 'Pagamento para o ' . htmlspecialchars($plano['nome_plano']),
        'source' => $token,
        'receipt_email' => $user['email'] // Envia um recibo para o email do utilizador
    ]);

    // Se o pagamento for bem-sucedido (charge.paid = true)
    if ($charge->paid) {
        $stmt_insert = $pdo->prepare(
            "INSERT INTO pagamento (id_usuario, id_plano, data_pagamento, status, id_transacao_api) VALUES (?, ?, CURDATE(), ?, ?)"
        );
        // Guarda o ID da transação da Stripe no seu banco de dados
        $stmt_insert->execute([$user['id_usuario'], $id_plano, 'Aprovado', $charge->id]);
        
        // Redireciona para uma página de sucesso
        header('Location: gestao_alunos.php?pagamento=sucesso');
        exit;
    } else {
        // Se a Stripe não confirmar o pagamento por algum motivo
        $erro = "O pagamento não pôde ser confirmado. Tente novamente.";
    }

} catch (\Stripe\Exception\CardException $e) {
    // O erro foi específico do cartão (recusado, inválido, etc.)
    $erro = 'O seu cartão foi recusado: ' . $e->getError()->message;
} catch (Throwable $e) {
    // Outro tipo de erro (ex: problema de conexão com a API)
    $erro = "Ocorreu um erro inesperado no processamento do pagamento. Por favor, tente novamente mais tarde.";
    // Para depuração, pode registar o erro: error_log($e->getMessage());
}

// Se chegou até aqui, houve um erro. Redireciona de volta para o checkout com a mensagem de erro.
header('Location: checkout.php?id_plano=' . $id_plano . '&erro=' . urlencode($erro));
exit;
