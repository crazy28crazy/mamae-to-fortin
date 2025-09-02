<?php
declare(strict_types=1);

// Garante que a sessão é iniciada apenas uma vez
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Configurações da Base de Dados
$DB_HOST = '127.0.0.1';
$DB_NAME = 'academia';
$DB_USER = 'root';
$DB_PASS = '';

// Ligação à Base de Dados com PDO e UTF-8 para corrigir acentos
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
    echo 'Erro de ligação à base de dados. Verifique o config.php';
    // Para depuração: error_log($e->getMessage());
    exit;
}

// --- Funções de Segurança (CSRF) ---
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

// --- Funções de Autenticação e Permissões ---

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function current_user(PDO $pdo): ?array {
    if (empty($_SESSION['user_id'])) return null;

    $sql = "
        SELECT u.id_usuario, u.nome, u.email, u.cpf, u.idade,
               GROUP_CONCAT(f.descricao) AS funcoes
        FROM usuario u
        LEFT JOIN usuario_funcao uf ON u.id_usuario = uf.id_usuario
        LEFT JOIN funcao f ON uf.id_funcao = f.id_funcao
        WHERE u.id_usuario = ?
        GROUP BY u.id_usuario
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$_SESSION['user_id']]);
    $user = $st->fetch();
    return $user ?: null;
}

function has_role(string $role, ?array $user): bool {
    if (!$user) return false;
    $funcoes = explode(',', $user['funcoes'] ?? '');
    return in_array($role, $funcoes, true);
}

function require_role(string $role): void {
    global $pdo;
    require_login();
    $user = current_user($pdo);
    if (!has_role($role, $user)) {
        header('Location: index.php'); 
        exit;
    }
}

// Funções de ajuda para verificar funções comuns
function is_admin(?array $user): bool {
    return has_role('Administrador', $user);
}
function is_aluno(?array $user): bool {
    return has_role('Aluno', $user);
}
function is_personal(?array $user): bool {
    return has_role('PersonalTrainer', $user);
}

