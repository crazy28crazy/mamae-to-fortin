<?php
$servidor = 'localhost';
$banco = 'academia';
$usuario = 'root';
$senha = '';

try {
    $pdo = new PDO("mysql:dbname=$banco;host=$servidor;charset=utf8", "$usuario", "$senha");
} catch (Exception $e) {
    echo 'Erro ao conectar ao banco de dados!<br><br>' . $e;
    exit();
}
?>