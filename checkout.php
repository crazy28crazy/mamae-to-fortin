<?php
require __DIR__ . '/config.php';
require_role('PersonalTrainer'); // Apenas personais podem aceder

$user = current_user($pdo);
$id_plano = (int)($_GET['id_plano'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM plano WHERE id_plano = ?");
$stmt->execute([$id_plano]);
$plano = $stmt->fetch();

if (!$plano) {
    header("Location: planos.php");
    exit;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Finalizar Pagamento</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body, .container { color: white; }
    .checkout-container {
        display: flex;
        gap: 30px;
        margin-top: 20px;
    }
    .resumo-plano, .form-pagamento {
        background-color: #2c2f33;
        padding: 25px;
        border-radius: 8px;
        border: 1px solid #444;
    }
    .resumo-plano { flex: 1; }
    .form-pagamento { flex: 2; }
    .resumo-plano h4 { margin-top: 0; color: #00aaff; }
    .resumo-plano ul { list-style-position: inside; padding-left: 0; margin-bottom: 20px; }
    .resumo-plano .preco { font-size: 1.2em; font-weight: bold; text-align: right; }
    
    /* --- CORREÇÃO FINAL APLICADA AQUI --- */
    #payment-form-row { /* Usar um ID tem a máxima prioridade */
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    #card-element {
        padding: 12px;
        border: 1px solid #777;
        border-radius: 4px;
        background-color: #36393f;
    }
    /* --- FIM DA CORREÇÃO --- */
  </style>
  <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
  <div class="container">
    <h2>Finalizar Contratação</h2>
    <p>Está a um passo de ativar o seu plano e começar a gerir os seus alunos.</p>
    <a href="planos.php">Voltar e escolher outro plano</a>
    <hr>
    
    <div class="checkout-container">
        <div class="resumo-plano">
            <h4>Resumo do Pedido</h4>
            <h3><?= htmlspecialchars($plano['nome_plano']) ?></h3>
            <ul>
                <?php foreach (explode(';', $plano['descricao']) as $beneficio): ?>
                    <li><?= htmlspecialchars($beneficio) ?></li>
                <?php endforeach; ?>
            </ul>
            <hr style="border-color: #444;">
            <p class="preco">Total: R$ <?= number_format($plano['preco'], 2, ',', '.') ?> / mês</p>
        </div>

        <div class="form-pagamento">
            <h4>Dados de Pagamento</h4>
            <p style="font-size: 0.9em;">Insira os dados do seu cartão de crédito. (Ambiente de testes)</p>
            <form action="processa_pagamento.php" method="post" id="payment-form">
                <input type="hidden" name="id_plano" value="<?= $plano['id_plano'] ?>">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                
                <!-- Adicionado o ID "payment-form-row" aqui -->
                <div class="form-row" id="payment-form-row">
                    <label for="card-element">Cartão de Crédito ou Débito</label>
                    <div id="card-element"></div>
                    <div id="card-errors" role="alert" style="color: #ff4d4d; margin-top: 10px;"></div>
                </div>
                <br>
                <button class="btn">Pagar e Ativar Plano</button>
            </form>
        </div>
    </div>
  </div>

  <script>
    var stripe = Stripe('pk_test_51BTUDGJAJfZb9HEBw3Gy63j48ss7B102213123...'); // Chave de teste
    var elements = stripe.elements({ locale: 'pt-BR' });
    var style = { base: { color: '#fff', '::placeholder': { color: '#aab7c4' } }, invalid: { color: '#fa755a' } };
    var card = elements.create('card', {style: style});
    card.mount('#card-element');

    var form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        stripe.createToken(card).then(function(result) {
            if (result.error) {
                document.getElementById('card-errors').textContent = result.error.message;
            } else {
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripeToken');
                hiddenInput.setAttribute('value', result.token.id);
                form.appendChild(hiddenInput);
                form.submit();
            }
        });
    });
  </script>
</body>
</html>

