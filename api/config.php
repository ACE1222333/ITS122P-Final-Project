<?php
/* ════════════════════════════════════════════════════════════════
   api/config.php — Database connection + shared helpers
════════════════════════════════════════════════════════════════ */

/* Capture every byte of accidental output (warnings, notices, HTML error pages) */
ob_start();

/* Suppress PHP error display — all errors return as JSON instead */
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

/* ── Response headers ───────────────────────────────────────── */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(204);
    exit;
}

/* ── Global exception handler — ensures uncaught exceptions return JSON ── */
set_exception_handler(function (Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'file'  => basename($e->getFile()),
        'line'  => $e->getLine(),
    ]);
    exit;
});

/* ── Shutdown handler — catches fatal errors (E_ERROR, E_PARSE, etc.) ── */
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'Fatal PHP error: ' . $err['message'],
            'file'  => basename($err['file']),
            'line'  => $err['line'],
        ]);
    }
});

/* ── Database config ────────────────────────────────────────── */
define('DB_HOST',    'localhost');
define('DB_NAME',    'carousell_db');
define('DB_USER',    'root');
define('DB_PASS',    '');          /* Default XAMPP password is empty */

define('UPLOAD_DIR', __DIR__ . '/../uploads/products/');
define('UPLOAD_URL', '/websystem/uploads/products/');

/* ── PDO singleton ───────────────────────────────────────────── */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn  = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        /* Let the exception propagate — it will be caught by set_exception_handler */
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    }
    return $pdo;
}

/* ── Response helpers ────────────────────────────────────────── */
function respond($data, int $code = 200): void {
    /* Discard everything buffered so far (warnings, notices, Xdebug HTML, etc.) */
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function respondError(string $msg, int $code = 400): void {
    respond(['error' => $msg], $code);
}

/* ── Body parser — php://input is cached so it can only be read once ── */
function _cachedInput(): string {
    static $cache = null;
    if ($cache === null) {
        $cache = (string) file_get_contents('php://input');
    }
    return $cache;
}

function getJsonBody(): array {
    $raw  = _cachedInput();
    if ($raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/* ── Token extraction ────────────────────────────────────────── */
function getRequestToken(): string {
    /* 1. Authorization: Bearer header.
       XAMPP Apache sometimes stores it under REDIRECT_HTTP_AUTHORIZATION
       depending on whether mod_rewrite is involved. */
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
        $val = $_SERVER[$key] ?? '';
        if ($val !== '' && preg_match('/^Bearer\s+(.+)$/i', $val, $m)) {
            return trim($m[1]);
        }
    }

    /* 2. JSON body — uses shared cache, safe to call alongside getJsonBody() */
    $body = getJsonBody();
    if (!empty($body['token'])) return (string) $body['token'];

    /* 3. Query string / POST field */
    if (!empty($_GET['token']))  return (string) $_GET['token'];
    if (!empty($_POST['token'])) return (string) $_POST['token'];

    return '';
}

/* ── Auth helpers ────────────────────────────────────────────── */
function generateToken(): string {
    return bin2hex(random_bytes(32));
}

function getAuthUser(): ?array {
    $token = getRequestToken();
    if ($token === '') return null;
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT u.user_id, u.first_name, u.last_name, u.email,
                    u.role, u.phone, u.address
             FROM sessions s
             JOIN users u ON u.user_id = s.user_id
             WHERE s.token = ? AND s.date_expiry > NOW()
             LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function requireAuth(): array {
    $user = getAuthUser();
    if (!$user) respondError('Unauthorized. Please log in.', 401);
    return $user;
}

function requireAdmin(): array {
    $user = requireAuth();
    if ($user['role'] !== 'admin') respondError('Forbidden. Admin access required.', 403);
    return $user;
}
