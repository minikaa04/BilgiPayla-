<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Oturum açmanız gerekli']);
    exit;
}

$itemId = (int)($_POST['item_id'] ?? 0);
$voteValue = (int)($_POST['vote'] ?? 0);

if (!$itemId || !in_array($voteValue, [1, -1])) {
    echo json_encode(['error' => 'Geçersiz istek']);
    exit;
}

$myId = $_SESSION['user_id'];

// Check existing vote
$stmt = $pdo->prepare("SELECT * FROM item_votes WHERE item_id = ? AND user_id = ?");
$stmt->execute([$itemId, $myId]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['vote'] == $voteValue) {
        // Remove vote (toggle off)
        $stmt = $pdo->prepare("DELETE FROM item_votes WHERE id = ?");
        $stmt->execute([$existing['id']]);
        // Decrement
        if ($voteValue == 1) {
            $pdo->prepare("UPDATE collection_items SET likes = GREATEST(likes - 1, 0) WHERE id = ?")->execute([$itemId]);
        } else {
            $pdo->prepare("UPDATE collection_items SET dislikes = GREATEST(dislikes - 1, 0) WHERE id = ?")->execute([$itemId]);
        }
        $userVote = 0;
    } else {
        // Change vote
        $stmt = $pdo->prepare("UPDATE item_votes SET vote = ? WHERE id = ?");
        $stmt->execute([$voteValue, $existing['id']]);
        if ($voteValue == 1) {
            $pdo->prepare("UPDATE collection_items SET likes = likes + 1, dislikes = GREATEST(dislikes - 1, 0) WHERE id = ?")->execute([$itemId]);
        } else {
            $pdo->prepare("UPDATE collection_items SET dislikes = dislikes + 1, likes = GREATEST(likes - 1, 0) WHERE id = ?")->execute([$itemId]);
        }
        $userVote = $voteValue;
    }
} else {
    // New vote
    $stmt = $pdo->prepare("INSERT INTO item_votes (item_id, user_id, vote) VALUES (?, ?, ?)");
    $stmt->execute([$itemId, $myId, $voteValue]);
    if ($voteValue == 1) {
        $pdo->prepare("UPDATE collection_items SET likes = likes + 1 WHERE id = ?")->execute([$itemId]);
    } else {
        $pdo->prepare("UPDATE collection_items SET dislikes = dislikes + 1 WHERE id = ?")->execute([$itemId]);
    }
    $userVote = $voteValue;
}

// Get updated counts
$stmt = $pdo->prepare("SELECT likes, dislikes FROM collection_items WHERE id = ?");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

echo json_encode([
    'success' => true,
    'likes' => $item['likes'],
    'dislikes' => $item['dislikes'],
    'user_vote' => $userVote
]);
