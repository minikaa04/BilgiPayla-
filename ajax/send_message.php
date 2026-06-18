<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Oturum açmanız gerekli']);
    exit;
}

$receiverId = (int)($_POST['receiver_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if (!$receiverId || !$content) {
    echo json_encode(['error' => 'Geçersiz istek']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $receiverId, $content]);

echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
