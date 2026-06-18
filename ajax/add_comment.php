<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error' => 'Auth']); exit; }

$itemId = (int)($_POST['item_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if ($itemId && $content) {
    $stmt = $pdo->prepare("INSERT INTO item_comments (item_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$itemId, $_SESSION['user_id'], $content]);
    
    // Notify item owner if someone else comments
    $stmt = $pdo->prepare("SELECT ci.user_id, ci.title, c.id as col_id FROM collection_items ci JOIN collections c ON ci.collection_id = c.id WHERE ci.id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    
    if ($item && $item['user_id'] != $_SESSION['user_id']) {
        $currentUser = getCurrentUser($pdo);
        $msg = $currentUser['display_name'] . ' "' . $item['title'] . '" bilgisine yorum yaptı.';
        createNotification($pdo, $item['user_id'], 'message', $msg, 'collection.php?id=' . $item['col_id']);
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Boş yorum yapılamaz']);
}
