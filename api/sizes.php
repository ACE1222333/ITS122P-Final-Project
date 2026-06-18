<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respondError('Method not allowed.', 405);

$fixed = [
    ['size_id' => 1, 'label' => 'XS'],
    ['size_id' => 2, 'label' => 'S'],
    ['size_id' => 3, 'label' => 'M'],
    ['size_id' => 4, 'label' => 'L'],
    ['size_id' => 5, 'label' => 'XL'],
    ['size_id' => 6, 'label' => 'XXL'],
    ['size_id' => 7, 'label' => 'Free Size'],
];

try {
    $db   = getDB();
    $stmt = $db->prepare('INSERT IGNORE INTO sizes (size_id, label) VALUES (?, ?)');
    foreach ($fixed as $s) { $stmt->execute([$s['size_id'], $s['label']]); }
} catch (Throwable $e) { /* non-fatal */ }

respond($fixed);
