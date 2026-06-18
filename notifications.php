<?php
$pageTitle = 'Bildirimler';
require_once 'includes/header.php';

// Mark all as read when visiting page
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$currentUser['id']]);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$currentUser['id']]);
$allNotifications = $stmt->fetchAll();
?>

<h1 class="page-title">
    <span class="icon"><i class="fas fa-bell"></i></span>
    Bildirimler
</h1>

<?php if (empty($allNotifications)): ?>
    <div class="empty-state">
        <div class="icon"><i class="fas fa-bell-slash"></i></div>
        <h3>Bildirim yok</h3>
        <p>Takip ettiğiniz koleksiyonlara yeni içerik eklendiğinde bildirim alacaksınız.</p>
    </div>
<?php else: ?>
    <?php foreach ($allNotifications as $notif): ?>
        <a href="<?= $notif['link'] ? BASE_URL . '/' . e($notif['link']) : '#' ?>" class="notification-item" style="display:flex;gap:12px;padding:16px 20px;background:var(--bg-white);border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border);margin-bottom:10px;text-decoration:none;color:inherit;">
            <div class="notif-icon">
                <i class="fas fa-<?= getNotifIcon($notif['type']) ?>"></i>
            </div>
            <div class="notif-content" style="flex:1;">
                <p style="font-size:0.9rem;color:var(--text);"><?= e($notif['message']) ?></p>
                <div class="notif-time" style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;"><?= timeAgo($notif['created_at']) ?></div>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
