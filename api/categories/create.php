<?php
/* ════════════════════════════════════════════════════════════════
   api/categories/create.php — Create a new category (admin only)
════════════════════════════════════════════════════════════════ */
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
requireAdmin();

$body = getJsonBody();
$name = trim($body['name']        ?? '');
$desc = trim($body['description'] ?? '');

if ($name === '') respondError('Category name is required.');
if (strlen($name) > 100) respondError('Category name must be 100 characters or fewer.');

try {
    $db = getDB();

    /* Prevent duplicates (case-insensitive) */
    $chk = $db->prepare('SELECT category_id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1');
    $chk->execute([$name]);
    if ($chk->fetch()) respondError('A category with that name already exists.');

    $ins = $db->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
    $ins->execute([$name, $desc ?: null]);
    $id  = (int) $db->lastInsertId();

    respond([
        'success'  => true,
        'category' => [
            'category_id' => $id,
            'name'        => $name,
            'description' => $desc,
        ],
    ]);
} catch (Throwable $e) {
    respondError('Failed to create category: ' . $e->getMessage(), 500);
}
