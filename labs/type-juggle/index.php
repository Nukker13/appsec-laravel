<?php
declare(strict_types=1);

session_start();

// --- Database bootstrap ---
$dbPath = __DIR__ . '/users.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    algorithm TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user'
)");

// Seed admin account — hash is a PHP magic hash (0e + digits only)
$admin = $db->query("SELECT id FROM users WHERE username = 'admin'")->fetch();
if (!$admin) {
    $db->prepare("INSERT INTO users (username, password_hash, algorithm, role) VALUES (?, ?, ?, ?)")
       ->execute(['admin', '0e830400451993494058024219903391', 'md5', 'admin']);
}

// --- Helpers ---
function computeHash(string $algo, string $password): string
{
    return match($algo) {
        'md5'    => md5($password),
        'sha1'   => sha1($password),
        'sha256' => hash('sha256', $password),
        default  => md5($password),
    };
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$error  = '';

// --- Logout ---
if ($uri === '/logout') {
    session_destroy();
    header('Location: /');
    exit;
}

// --- Register ---
if ($uri === '/register') {
    if ($method === 'POST') {
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $algo      = $_POST['algorithm'] ?? 'md5';
        $validAlgos = ['md5', 'sha1', 'sha256'];

        if (!$username || !$password) {
            $error = 'All fields are required.';
        } elseif (!in_array($algo, $validAlgos, true)) {
            $error = 'Invalid algorithm.';
        } else {
            try {
                $hash = computeHash($algo, $password);
                $db->prepare("INSERT INTO users (username, password_hash, algorithm) VALUES (?, ?, ?)")
                   ->execute([$username, $hash, $algo]);
                header('Location: /');
                exit;
            } catch (\PDOException $e) {
                $error = 'Username already taken.';
            }
        }
    }
    include __DIR__ . '/views/register.php';
    exit;
}

// --- Login ---
if ($uri === '/' && $method === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = $db->prepare("SELECT * FROM users WHERE username = ?");
    $user->execute([$username]);
    $user = $user->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $computed = computeHash($user['algorithm'], $password);
        // VULNERABLE: loose comparison allows magic hash bypass
        if ($computed == $user['password_hash']) {
            $_SESSION['user'] = $user;
            header('Location: /');
            exit;
        }
    }
    $error = 'Invalid username or password.';
}

// --- Dashboard or login form ---
if (currentUser()) {
    include __DIR__ . '/views/dashboard.php';
} else {
    include __DIR__ . '/views/login.php';
}
