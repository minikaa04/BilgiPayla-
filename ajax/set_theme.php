<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error' => 'Auth']); exit; }

$theme = in_array($_POST['theme'] ?? '', ['light', 'dark']) ? $_POST['theme'] : 'light';
$pdo->prepare("UPDATE users SET theme = ? WHERE id = ?")->execute([$theme, $_SESSION['user_id']]);

echo json_encode(['success' => true]);
