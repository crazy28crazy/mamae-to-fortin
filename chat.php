<?php
require __DIR__ . '/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $remetente_id = current_user($pdo)['id_usuario'];
    $destinatario_id = (int)($_POST['destinatario_id'] ?? 0);
    $conteudo = trim($_POST['conteudo'] ?? '');

    if ($destinatario_id && $conteudo !== '') {
        $st = $pdo->prepare("INSERT INTO Mensagem (id_remetente, id_destinatario, conteudo, data_envio) VALUES (?, ?, ?, NOW())");
        $st->execute([$remetente_id, $destinatario_id, $conteudo]);
    }
}

header("Location: index.php");
exit;
