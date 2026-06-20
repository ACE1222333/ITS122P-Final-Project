<?php
/* ════════════════════════════════════════════════════════════════
   api/auth.php — Login · Register · Logout · Session check
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

/* ── GET: validate current session token ─────────────────────── */
if ($method === 'GET') {
    $user  = getAuthUser();
    $token = getRequestToken();
    if (!$user) {
        respond(['success' => false, 'user' => null]);
    }
    respond([
        'success' => true,
        'user'    => [
            'user_id'    => (int) $user['user_id'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'email'      => $user['email'],
            'role'       => $user['role'],
            'phone'      => $user['phone']   ?? '',
            'address'    => $user['address'] ?? '',
            'token'      => $token,
        ],
    ]);
}

if ($method !== 'POST') respondError('Method not allowed.', 405);

$body   = getJsonBody();
$action = $body['action'] ?? '';

/* ══════════════════════════════════════════════════
   LOGIN
══════════════════════════════════════════════════ */
if ($action === 'login') {
    $email    = trim($body['email']    ?? '');
    $password = trim($body['password'] ?? '');

    if ($email === '' || $password === '') {
        respondError('Email and password are required.');
    }

    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        respondError('Database error during login: ' . $e->getMessage(), 500);
    }

    if (!$user || !password_verify($password, $user['password'])) {
        respondError('Incorrect email or password.', 401);
    }

    try {
        $token  = generateToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
        $db->prepare('INSERT INTO sessions (user_id, token, date_expiry) VALUES (?, ?, ?)')
           ->execute([(int) $user['user_id'], $token, $expiry]);
    } catch (Throwable $e) {
        respondError('Failed to create session: ' . $e->getMessage(), 500);
    }

    /* Store in PHP session — browser sends PHPSESSID cookie automatically */
    $authUser = [
        'user_id'    => (int) $user['user_id'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'phone'      => $user['phone']   ?? '',
        'address'    => $user['address'] ?? '',
    ];
    $_SESSION['auth_user'] = $authUser;

    respond([
        'success' => true,
        'user'    => array_merge($authUser, ['token' => $token]),
    ]);
}

/* ══════════════════════════════════════════════════
   REGISTER
══════════════════════════════════════════════════ */
if ($action === 'register') {
    $fname    = trim($body['first_name'] ?? '');
    $lname    = trim($body['last_name']  ?? '');
    $email    = trim($body['email']      ?? '');
    $phone    = trim($body['phone']      ?? '');
    $address  = trim($body['address']    ?? '');
    $password = trim($body['password']   ?? '');

    if ($fname === '' || $lname === '') respondError('First and last name are required.');
    if ($email === '')                  respondError('Email is required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respondError('Invalid email address.');
    if (strlen($password) < 8)          respondError('Password must be at least 8 characters.');

    try {
        $db = getDB();

        /* Check unique email */
        $chk = $db->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $chk->execute([$email]);
        if ($chk->fetch()) respondError('An account with that email already exists.');

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins  = $db->prepare(
            'INSERT INTO users (first_name, last_name, email, phone, address, role, password)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$fname, $lname, $email, $phone, $address, 'customer', $hash]);
        $userId = (int) $db->lastInsertId();

        $token  = generateToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
        $db->prepare('INSERT INTO sessions (user_id, token, date_expiry) VALUES (?, ?, ?)')
           ->execute([$userId, $token, $expiry]);
    } catch (Throwable $e) {
        respondError('Registration failed: ' . $e->getMessage(), 500);
    }

    $authUser = [
        'user_id'    => $userId,
        'first_name' => $fname,
        'last_name'  => $lname,
        'email'      => $email,
        'role'       => 'customer',
        'phone'      => $phone,
        'address'    => $address,
    ];
    $_SESSION['auth_user'] = $authUser;

    respond([
        'success' => true,
        'user'    => array_merge($authUser, ['token' => $token]),
    ]);
}

/* ══════════════════════════════════════════════════
   LOGOUT
══════════════════════════════════════════════════ */
if ($action === 'logout') {
    /* Destroy PHP session */
    $_SESSION = [];
    session_destroy();

    $token = getRequestToken();
    if ($token !== '') {
        try {
            getDB()->prepare('DELETE FROM sessions WHERE token = ?')->execute([$token]);
        } catch (Throwable $e) { /* ignore logout errors */ }
    }
    respond(['success' => true]);
}

respondError('Unknown action. Expected: login | register | logout');
