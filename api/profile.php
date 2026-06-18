<?php
/* ════════════════════════════════════════════════════════════════
   api/profile.php
     GET  — return current user's profile data
     POST — update profile (name, email, phone, address, password)
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();   /* Both GET and POST require a valid session */

/* ══════════════ GET — fetch current profile ════════════════ */
if ($method === 'GET') {
    try {
        $row = getDB()->prepare(
            'SELECT user_id, first_name, last_name, email, phone, address, role
             FROM users WHERE user_id = ? LIMIT 1'
        );
        $row->execute([$user['user_id']]);
        $profile = $row->fetch();
        if (!$profile) respondError('User not found.', 404);
        respond([
            'user_id'    => (int) $profile['user_id'],
            'first_name' => $profile['first_name'],
            'last_name'  => $profile['last_name'],
            'email'      => $profile['email'],
            'phone'      => $profile['phone']   ?? '',
            'address'    => $profile['address'] ?? '',
            'role'       => $profile['role'],
        ]);
    } catch (Throwable $e) {
        respondError('Failed to load profile: ' . $e->getMessage(), 500);
    }
}

/* ══════════════ POST — update profile ══════════════════════ */
if ($method !== 'POST') respondError('Method not allowed.', 405);

$body = getJsonBody();

$fname    = trim($body['first_name'] ?? '');
$lname    = trim($body['last_name']  ?? '');
$email    = trim($body['email']      ?? '');
$phone    = trim($body['phone']      ?? '');
$address  = trim($body['address']    ?? '');
$curPass  = $body['current_password'] ?? '';
$newPass  = $body['new_password']     ?? '';

/* ── Validation ─────────────────────────────────────────────── */
if ($fname === '' || $lname === '') respondError('First and last name are required.');
if ($email === '')                  respondError('Email is required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respondError('Invalid email address.');

$changingPassword = ($newPass !== '');
if ($changingPassword) {
    if ($curPass === '')          respondError('Current password is required to set a new password.');
    if (strlen($newPass) < 8)     respondError('New password must be at least 8 characters.');
}

try {
    $db = getDB();

    /* Fetch current user record for password verification */
    $stmt = $db->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user['user_id']]);
    $current = $stmt->fetch();
    if (!$current) respondError('User account not found.', 404);

    /* Verify current password if changing */
    if ($changingPassword && !password_verify($curPass, $current['password'])) {
        respondError('Current password is incorrect.');
    }

    /* Check email uniqueness (allow keeping the same email) */
    if ($email !== $current['email']) {
        $chk = $db->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ? LIMIT 1');
        $chk->execute([$email, $user['user_id']]);
        if ($chk->fetch()) respondError('That email address is already in use by another account.');
    }

    /* Build the UPDATE query */
    if ($changingPassword) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $upd  = $db->prepare(
            'UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=?, password=?
             WHERE user_id=?'
        );
        $upd->execute([$fname, $lname, $email, $phone, $address, $hash, $user['user_id']]);
    } else {
        $upd = $db->prepare(
            'UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=?
             WHERE user_id=?'
        );
        $upd->execute([$fname, $lname, $email, $phone, $address, $user['user_id']]);
    }

    respond([
        'success'    => true,
        'user'       => [
            'user_id'    => (int) $user['user_id'],
            'first_name' => $fname,
            'last_name'  => $lname,
            'email'      => $email,
            'phone'      => $phone,
            'address'    => $address,
            'role'       => $current['role'],
        ],
    ]);

} catch (Throwable $e) {
    respondError('Failed to update profile: ' . $e->getMessage(), 500);
}
