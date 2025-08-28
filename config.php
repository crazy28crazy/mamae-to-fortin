<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$DB_HOST = '127.0.0.1';
$DB_NAME = 'academia';
$DB_USER = 'root';
$DB_PASS = '';
$BASE_URL = '/';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Erro de conexão ao banco. Verifique config.php';
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']);
        if (!$ok) {
            http_response_code(400);
            exit('CSRF inválido.');
        }
    }
}


function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        // CORREÇÃO: Redireciona para 'login.php' se o usuário não estiver logado.
        header('Location: login.php');
        exit;
    }
}

function current_user(PDO $pdo): ?array {
    if (empty($_SESSION['user_id'])) return null;

    // Esta função busca os dados do usuário e suas funções (papéis) no sistema.
    $sql = "
        SELECT U.id_usuario, U.nome, U.email, U.cpf, U.idade,
               GROUP_CONCAT(F.descricao) AS funcoes
        FROM Usuario U
        LEFT JOIN Usuario_Funcao UF ON U.id_usuario = UF.id_usuario
        LEFT JOIN Funcao F ON UF.id_funcao = F.id_funcao
        WHERE U.id_usuario = ?
        GROUP BY U.id_usuario
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$_SESSION['user_id']]);
    return $st->fetch() ?: null;
}

function is_admin(array $u=null): bool {
    if (!$u) return false;
    $funcoes = explode(',', $u['funcoes'] ?? '');
    return in_array('Administrador', $funcoes, true);
}

?>
