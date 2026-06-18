<?php
$pageTitle = 'Profil';
require_once 'config.php';
require_once 'helpers.php';
requireLogin();

$profileId = (int)($_GET['id'] ?? $_SESSION['user_id']);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profileId]);
$profile = $stmt->fetch();

if (!$profile) {
    redirect('index.php');
}

$isOwn = ($profile['id'] === $_SESSION['user_id']);

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'");
$stmt->execute([$profileId, $profileId]);
$friendCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM collections WHERE user_id = ?");
$stmt->execute([$profileId]);
$collectionCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM collection_items WHERE user_id = ? AND status = 'approved'");
$stmt->execute([$profileId]);
$contributionCount = $stmt->fetchColumn();

// Friend status
$friendStatus = null;
if (!$isOwn) {
    $friendStatus = friendRequestExists($pdo, $_SESSION['user_id'], $profileId);
}

// User's collections
$stmt = $pdo->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM collection_items WHERE collection_id = c.id AND status = 'approved') as item_count,
    (SELECT COUNT(*) FROM collection_followers WHERE collection_id = c.id) as follower_count
    FROM collections c WHERE c.user_id = ? ORDER BY c.created_at DESC");
$stmt->execute([$profileId]);
$collections = $stmt->fetchAll();

$pageTitle = $profile['display_name'] ?: $profile['username'];
require_once 'includes/header.php';
?>

<div class="profile-header">
    <div class="profile-cover"></div>
    <div class="profile-info">
        <img src="<?= avatar($profile['avatar']) ?>" class="profile-avatar" alt="avatar">
        <div class="profile-details">
            <h2><?= e($profile['display_name'] ?: $profile['username']) ?></h2>
            <div class="username">@<?= e($profile['username']) ?></div>
            <?php if ($profile['bio']): ?>
                <div class="bio"><?= e($profile['bio']) ?></div>
            <?php endif; ?>
            <div class="profile-stats">
                <div class="profile-stat">
                    <div class="num"><?= $friendCount ?></div>
                    <div class="label">Arkadaş</div>
                </div>
                <div class="profile-stat">
                    <div class="num"><?= $collectionCount ?></div>
                    <div class="label">Koleksiyon</div>
                </div>
                <div class="profile-stat">
                    <div class="num"><?= $contributionCount ?></div>
                    <div class="label">Katkı</div>
                </div>
            </div>
        </div>
        <div class="profile-actions">
            <?php if ($isOwn): ?>
                <a href="<?= BASE_URL ?>/edit_profile.php" class="btn btn-secondary"><i class="fas fa-edit"></i> Profili Düzenle</a>
            <?php else: ?>
                <?php if (!$friendStatus): ?>
                    <button class="btn btn-primary" onclick="friendAction('add', <?= $profileId ?>)"><i class="fas fa-user-plus"></i> Arkadaş Ekle</button>
                <?php elseif ($friendStatus['status'] === 'pending' && $friendStatus['user_id'] == $_SESSION['user_id']): ?>
                    <button class="btn btn-secondary" disabled><i class="fas fa-clock"></i> İstek Gönderildi</button>
                <?php elseif ($friendStatus['status'] === 'pending' && $friendStatus['friend_id'] == $_SESSION['user_id']): ?>
                    <button class="btn btn-success" onclick="friendAction('accept', <?= $profileId ?>)"><i class="fas fa-check"></i> Kabul Et</button>
                <?php elseif ($friendStatus['status'] === 'accepted'): ?>
                    <button class="btn btn-secondary" onclick="friendAction('remove', <?= $profileId ?>)"><i class="fas fa-user-minus"></i> Arkadaşlıktan Çıkar</button>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/messages.php?user=<?= $profileId ?>" class="btn btn-secondary"><i class="fas fa-envelope"></i> Mesaj Gönder</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<h2 class="page-title" style="font-size:1.2rem;">
    <span class="icon"><i class="fas fa-folder"></i></span>
    Koleksiyonlar
</h2>

<?php if (empty($collections)): ?>
    <div class="empty-state">
        <div class="icon"><i class="fas fa-folder-open"></i></div>
        <h3>Henüz koleksiyon yok</h3>
    </div>
<?php else: ?>
    <div class="collection-grid">
        <?php foreach ($collections as $col): ?>
            <a href="<?= BASE_URL ?>/collection.php?id=<?= $col['id'] ?>" class="collection-card" style="text-decoration:none;color:inherit;">
                <div class="collection-card-cover">
                    <img src="<?= coverImage($col['cover_image']) ?>" alt="<?= e($col['title']) ?>">
                    <span class="collection-card-category"><?= e($col['category']) ?></span>
                </div>
                <div class="collection-card-body">
                    <h3><?= e($col['title']) ?></h3>
                    <p><?= e($col['description']) ?></p>
                    <div class="collection-card-meta">
                        <span><i class="fas fa-layer-group"></i> <?= $col['item_count'] ?> içerik</span>
                        <span><i class="fas fa-users"></i> <?= $col['follower_count'] ?> takipçi</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function friendAction(action, userId) {
    ajaxPost('ajax/friend_action.php', {action: action, user_id: userId})
    .then(data => {
        if(data.success) location.reload();
        else alert(data.error || 'Bir hata oluştu');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
