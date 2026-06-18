<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Oturum açmanız gerekli']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$itemId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['error' => 'Geçersiz istek']);
    exit;
}

// Check ownership
$stmt = $pdo->prepare("SELECT ci.*, c.user_id as owner_id FROM collection_items ci JOIN collections c ON ci.collection_id = c.id WHERE ci.id = ?");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item || $item['owner_id'] != $_SESSION['user_id']) {
    echo json_encode(['error' => 'Yetkiniz yok']);
    exit;
}

$newStatus = $action === 'approve' ? 'approved' : 'rejected';
$stmt = $pdo->prepare("UPDATE collection_items SET status = ? WHERE id = ?");
$stmt->execute([$newStatus, $itemId]);

// Send notifications
if ($action === 'approve') {
    // Get collection info
    $stmt = $pdo->prepare("SELECT c.title, c.id FROM collections c JOIN collection_items ci ON ci.collection_id = c.id WHERE ci.id = ?");
    $stmt->execute([$itemId]);
    $col = $stmt->fetch();

    // Notify contributor
    $msg = '"' . $col['title'] . '" koleksiyonundaki katkınız onaylandı!';
    createNotification($pdo, $item['user_id'], 'approved', $msg, 'collection.php?id=' . $col['id']);

    // Notify followers
    $stmt2 = $pdo->prepare("SELECT u.display_name FROM users u WHERE u.id = ?");
    $stmt2->execute([$item['user_id']]);
    $contributor = $stmt2->fetch();
    $fMsg = '"' . $col['title'] . '" koleksiyonuna yeni içerik eklendi: ' . $item['title'];
    notifyCollectionFollowers($pdo, $col['id'], 'new_item', $fMsg, 'collection.php?id=' . $col['id'], $item['user_id']);
}

echo json_encode(['success' => true]);
