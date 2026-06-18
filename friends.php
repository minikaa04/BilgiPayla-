<?php
$pageTitle = 'Arkadaşlar';
require_once 'includes/header.php';

$tab = $_GET['tab'] ?? 'friends';

// Accepted friends
$stmt = $pdo->prepare("SELECT u.* FROM friends f 
    JOIN users u ON (CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END) = u.id
    WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'
    ORDER BY f.created_at DESC");
$stmt->execute([$currentUser['id'], $currentUser['id'], $currentUser['id']]);
$friends = $stmt->fetchAll();

// Pending requests (received)
$stmt = $pdo->prepare("SELECT u.*, f.id as request_id FROM friends f 
    JOIN users u ON f.user_id = u.id
    WHERE f.friend_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC");
$stmt->execute([$currentUser['id']]);
$pendingRequests = $stmt->fetchAll();

// Sent requests
$stmt = $pdo->prepare("SELECT u.*, f.id as request_id FROM friends f 
    JOIN users u ON f.friend_id = u.id
    WHERE f.user_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC");
$stmt->execute([$currentUser['id']]);
$sentRequests = $stmt->fetchAll();
?>

<h1 class="page-title">
    <span class="icon"><i class="fas fa-user-friends"></i></span>
    Arkadaşlar
</h1>

<div class="tabs">
    <a href="?tab=friends" class="tab <?= $tab === 'friends' ? 'active' : '' ?>">
        Arkadaşlarım (<?= count($friends) ?>)
    </a>
    <a href="?tab=pending" class="tab <?= $tab === 'pending' ? 'active' : '' ?>">
        Gelen İstekler (<?= count($pendingRequests) ?>)
    </a>
    <a href="?tab=sent" class="tab <?= $tab === 'sent' ? 'active' : '' ?>">
        Gönderilen İstekler (<?= count($sentRequests) ?>)
    </a>
</div>

<?php if ($tab === 'friends'): ?>
    <?php if (empty($friends)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-user-friends"></i></div>
            <h3>Henüz arkadaşınız yok</h3>
            <p>Kullanıcı arayarak arkadaş ekleyebilirsiniz.</p>
            <br>
            <a href="<?= BASE_URL ?>/search_users.php" class="btn btn-primary"><i class="fas fa-search"></i> Kullanıcı Ara</a>
        </div>
    <?php else: ?>
        <?php foreach ($friends as $friend): ?>
            <div class="friend-card" id="friend-<?= $friend['id'] ?>">
                <a href="<?= BASE_URL ?>/profile.php?id=<?= $friend['id'] ?>">
                    <img src="<?= avatar($friend['avatar']) ?>" alt="">
                </a>
                <div class="friend-card-info">
                    <h4><a href="<?= BASE_URL ?>/profile.php?id=<?= $friend['id'] ?>"><?= e($friend['display_name'] ?: $friend['username']) ?></a></h4>
                    <span>@<?= e($friend['username']) ?></span>
                </div>
                <div class="friend-card-actions">
                    <a href="<?= BASE_URL ?>/messages.php?user=<?= $friend['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-envelope"></i></a>
                    <button class="btn btn-danger btn-sm" onclick="friendAction('remove', <?= $friend['id'] ?>)"><i class="fas fa-user-minus"></i></button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php elseif ($tab === 'pending'): ?>
    <?php if (empty($pendingRequests)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-inbox"></i></div>
            <h3>Bekleyen istek yok</h3>
        </div>
    <?php else: ?>
        <?php foreach ($pendingRequests as $req): ?>
            <div class="friend-card" id="request-<?= $req['id'] ?>">
                <a href="<?= BASE_URL ?>/profile.php?id=<?= $req['id'] ?>">
                    <img src="<?= avatar($req['avatar']) ?>" alt="">
                </a>
                <div class="friend-card-info">
                    <h4><a href="<?= BASE_URL ?>/profile.php?id=<?= $req['id'] ?>"><?= e($req['display_name'] ?: $req['username']) ?></a></h4>
                    <span>@<?= e($req['username']) ?></span>
                </div>
                <div class="friend-card-actions">
                    <button class="btn btn-success btn-sm" onclick="friendAction('accept', <?= $req['id'] ?>)"><i class="fas fa-check"></i> Kabul Et</button>
                    <button class="btn btn-danger btn-sm" onclick="friendAction('reject', <?= $req['id'] ?>)"><i class="fas fa-times"></i> Reddet</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php else: ?>
    <?php if (empty($sentRequests)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-paper-plane"></i></div>
            <h3>Gönderilmiş istek yok</h3>
        </div>
    <?php else: ?>
        <?php foreach ($sentRequests as $req): ?>
            <div class="friend-card">
                <a href="<?= BASE_URL ?>/profile.php?id=<?= $req['id'] ?>">
                    <img src="<?= avatar($req['avatar']) ?>" alt="">
                </a>
                <div class="friend-card-info">
                    <h4><a href="<?= BASE_URL ?>/profile.php?id=<?= $req['id'] ?>"><?= e($req['display_name'] ?: $req['username']) ?></a></h4>
                    <span>@<?= e($req['username']) ?></span>
                </div>
                <div class="friend-card-actions">
                    <span class="pending-badge pending"><i class="fas fa-clock"></i> Bekliyor</span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
