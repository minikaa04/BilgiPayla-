<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Oturum açmanız gerekli']);
    exit;
}

$action = $_POST['action'] ?? '';
$userId = (int)($_POST['user_id'] ?? 0);
$myId = $_SESSION['user_id'];

if (!$userId || $userId === $myId) {
    echo json_encode(['error' => 'Geçersiz istek']);
    exit;
}

switch ($action) {
    case 'add':
        $existing = friendRequestExists($pdo, $myId, $userId);
        if (!$existing) {
            $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$myId, $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'İstek zaten mevcut']);
        }
        break;

    case 'accept':
        $stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
        $stmt->execute([$userId, $myId]);
        echo json_encode(['success' => $stmt->rowCount() > 0]);
        break;

    case 'reject':
        $stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
        $stmt->execute([$userId, $myId]);
        echo json_encode(['success' => true]);
        break;

    case 'remove':
        $stmt = $pdo->prepare("DELETE FROM friends WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND status = 'accepted'");
        $stmt->execute([$myId, $userId, $userId, $myId]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Geçersiz işlem']);
}
