<?php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function redirect($path) {
    header('Location: ' . BASE_URL . '/' . $path);
    exit;
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' yıl önce';
    if ($diff->m > 0) return $diff->m . ' ay önce';
    if ($diff->d > 0) return $diff->d . ' gün önce';
    if ($diff->h > 0) return $diff->h . ' saat önce';
    if ($diff->i > 0) return $diff->i . ' dakika önce';
    return 'Az önce';
}

function uploadImage($file, $subdir = '') {
    $targetDir = UPLOAD_DIR . ($subdir ? $subdir . '/' : '');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) return false;

    $filename = uniqid() . '_' . time() . '.' . $ext;
    $target = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return ($subdir ? $subdir . '/' : '') . $filename;
    }
    return false;
}

function getUnreadMessageCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function getPendingFriendRequestCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE friend_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function areFriends($pdo, $userId1, $userId2) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND status = 'accepted'");
    $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
    return $stmt->fetchColumn() > 0;
}

function friendRequestExists($pdo, $userId1, $userId2) {
    $stmt = $pdo->prepare("SELECT * FROM friends WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))");
    $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
    return $stmt->fetch();
}

function avatar($path) {
    if (!$path || $path === 'default.png') {
        return BASE_URL . '/assets/default_avatar.png';
    }
    return BASE_URL . '/uploads/' . $path;
}

function coverImage($path) {
    if (!$path || $path === 'default_cover.png') {
        return BASE_URL . '/assets/default_cover.jpg';
    }
    return BASE_URL . '/uploads/' . $path;
}

function getNotifIcon($type) {
    switch ($type) {
        case 'new_item': return 'layer-group';
        case 'friend_request': return 'user-plus';
        case 'friend_accept': return 'user-check';
        case 'contribution': return 'plus-circle';
        case 'approved': return 'check-circle';
        case 'message': return 'envelope';
        default: return 'bell';
    }
}

function createNotification($pdo, $userId, $type, $message, $link = null) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $message, $link]);
}

function notifyCollectionFollowers($pdo, $collectionId, $type, $message, $link, $excludeUserId = null) {
    $stmt = $pdo->prepare("SELECT user_id FROM collection_followers WHERE collection_id = ?");
    $stmt->execute([$collectionId]);
    $followers = $stmt->fetchAll();
    foreach ($followers as $f) {
        if ($excludeUserId && $f['user_id'] == $excludeUserId) continue;
        createNotification($pdo, $f['user_id'], $type, $message, $link);
    }
}

function isAdmin($user) {
    return ($user['role'] ?? 'user') === 'admin';
}

function requireAdmin($user) {
    if (!isAdmin($user)) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}
