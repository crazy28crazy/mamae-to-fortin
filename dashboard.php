<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Bem-vindo, <?php echo $_SESSION['nome']; ?>!</h1>
    <p>Tipo de conta: <?php echo $_SESSION['tipo']; ?></p>
    <nav>
        <a href="perfil.php">Editar Perfil</a> |
        <a href="logout.php">Sair</a>
    </nav>
</body>
</html>

