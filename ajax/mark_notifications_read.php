<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error' => 'Auth']); exit; }

$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
echo json_encode(['success' => true]);
