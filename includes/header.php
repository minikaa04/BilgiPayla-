<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
requireLogin();
$currentUser = getCurrentUser($pdo);
$unreadMessages = getUnreadMessageCount($pdo, $currentUser['id']);
$pendingFriends = getPendingFriendRequestCount($pdo, $currentUser['id']);

// Notification count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$currentUser['id']]);
$unreadNotifications = $stmt->fetchColumn();

// Recent notifications for dropdown
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$currentUser['id']]);
$notifications = $stmt->fetchAll();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$userTheme = $currentUser['theme'] ?? 'light';
$isAdmin = ($currentUser['role'] ?? 'user') === 'admin';
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?= e($userTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="BilgiPaylaş - Bilgi paylaşım sosyal ağı.">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        // Apply theme before page renders to prevent flash
        (function() {
            const saved = localStorage.getItem('bp_theme') || '<?= e($userTheme) ?>';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</head>
<body>

<!-- Header -->
<header class="header">
    <a href="<?= BASE_URL ?>/index.php" class="header-logo">
        <div class="logo-icon">
            <i class="fas fa-lightbulb"></i>
        </div>
        BilgiPaylaş
    </a>
    <div class="header-right">
        <!-- Theme Toggle -->
        <button class="theme-toggle" onclick="toggleTheme()" title="Tema Değiştir" id="themeBtn">
            <i class="fas fa-<?= $userTheme === 'dark' ? 'sun' : 'moon' ?>"></i>
        </button>

        <!-- Notifications -->
        <div style="position:relative;">
            <button class="header-btn" onclick="toggleNotifications()" title="Bildirimler" id="notifBtn">
                <i class="fas fa-bell"></i>
                <?php if($unreadNotifications > 0): ?>
                    <span class="badge" id="notifBadge"><?= $unreadNotifications ?></span>
                <?php endif; ?>
            </button>
            <div class="notification-dropdown" id="notifDropdown">
                <div class="notification-dropdown-header">
                    <span><i class="fas fa-bell"></i> Bildirimler</span>
                    <?php if($unreadNotifications > 0): ?>
                        <a href="#" onclick="markAllRead();return false;" style="font-size:0.8rem;font-weight:500;">Tümünü Okundu İşaretle</a>
                    <?php endif; ?>
                </div>
                <?php if (empty($notifications)): ?>
                    <div style="padding:30px;text-align:center;color:var(--text-muted);font-size:0.85rem;">
                        Bildirim yok
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <a href="<?= $notif['link'] ? BASE_URL . '/' . e($notif['link']) : '#' ?>" class="notification-item <?= !$notif['is_read'] ? 'unread' : '' ?>">
                            <div class="notif-icon">
                                <i class="fas fa-<?= getNotifIcon($notif['type']) ?>"></i>
                            </div>
                            <div class="notif-content">
                                <p><?= e($notif['message']) ?></p>
                                <div class="notif-time"><?= timeAgo($notif['created_at']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <a href="<?= BASE_URL ?>/messages.php" class="header-btn" title="Mesajlar">
            <i class="fas fa-envelope"></i>
            <?php if($unreadMessages > 0): ?>
                <span class="badge"><?= $unreadMessages ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/friends.php" class="header-btn" title="Arkadaşlar">
            <i class="fas fa-user-friends"></i>
            <?php if($pendingFriends > 0): ?>
                <span class="badge"><?= $pendingFriends ?></span>
            <?php endif; ?>
        </a>

        <?php if ($isAdmin): ?>
            <a href="<?= BASE_URL ?>/admin.php" class="header-btn" title="Yönetim Paneli" style="color:var(--danger);">
                <i class="fas fa-shield-alt"></i>
            </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/profile.php?id=<?= $currentUser['id'] ?>" style="display:flex;align-items:center;gap:8px;text-decoration:none;">
            <img src="<?= avatar($currentUser['avatar']) ?>" class="header-avatar" alt="avatar">
            <span class="header-user-name"><?= e($currentUser['display_name'] ?: $currentUser['username']) ?></span>
        </a>
        <a href="<?= BASE_URL ?>/logout.php" class="header-btn" title="Çıkış Yap">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</header>

<?php include __DIR__ . '/sidebar.php'; ?>

<main class="main-content">

<script>
function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme');
    const newTheme = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('bp_theme', newTheme);
    document.getElementById('themeBtn').innerHTML = '<i class="fas fa-' + (newTheme === 'dark' ? 'sun' : 'moon') + '"></i>';
    // Save to server
    fetch(BASE_URL + '/ajax/set_theme.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'theme=' + newTheme
    });
}

function toggleNotifications() {
    const dd = document.getElementById('notifDropdown');
    dd.classList.toggle('show');
}

function markAllRead() {
    fetch(BASE_URL + '/ajax/mark_notifications_read.php', { method: 'POST' })
    .then(() => {
        document.querySelectorAll('.notification-item.unread').forEach(el => el.classList.remove('unread'));
        const badge = document.getElementById('notifBadge');
        if (badge) badge.remove();
    });
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    const notifBtn = document.getElementById('notifBtn');
    const dd = document.getElementById('notifDropdown');
    if (dd && !dd.contains(e.target) && !notifBtn.contains(e.target)) {
        dd.classList.remove('show');
    }
});
</script>
