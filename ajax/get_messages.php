<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Oturum açmanız gerekli']);
    exit;
}

$userId = (int)($_GET['user_id'] ?? 0);
$lastCount = (int)($_GET['last_id'] ?? 0);

if (!$userId) {
    echo json_encode(['messages' => []]);
    exit;
}

// Mark messages as read
$stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$stmt->execute([$userId, $_SESSION['user_id']]);

// Get new messages from the other user
$stmt = $pdo->prepare("SELECT * FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 1 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId, $_SESSION['user_id']]);
$messages = $stmt->fetchAll();

// Simple approach: return recent messages from the other user
$allMsgs = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
$allMsgs->execute([$_SESSION['user_id'], $userId, $userId, $_SESSION['user_id']]);
$all = $allMsgs->fetchAll();

$totalCount = count($all);
$newMessages = [];

if ($totalCount > $lastCount) {
    $newOnes = array_slice($all, $lastCount);
    foreach ($newOnes as $msg) {
        if ($msg['sender_id'] == $userId) {
            $newMessages[] = $msg;
        }
    }
}

echo json_encode(['messages' => $newMessages, 'total' => $totalCount]);
