<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Oturum açmanız gerekli']);
    exit;
}

$collectionId = (int)($_POST['collection_id'] ?? 0);
if (!$collectionId) {
    echo json_encode(['error' => 'Geçersiz istek']);
    exit;
}

$myId = $_SESSION['user_id'];

// Check if already following
$stmt = $pdo->prepare("SELECT id FROM collection_followers WHERE collection_id = ? AND user_id = ?");
$stmt->execute([$collectionId, $myId]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $pdo->prepare("DELETE FROM collection_followers WHERE id = ?");
    $stmt->execute([$existing['id']]);
    echo json_encode(['success' => true, 'following' => false]);
} else {
    $stmt = $pdo->prepare("INSERT INTO collection_followers (collection_id, user_id) VALUES (?, ?)");
    $stmt->execute([$collectionId, $myId]);
    echo json_encode(['success' => true, 'following' => true]);
}
