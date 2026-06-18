<?php
$pageTitle = 'Kullanıcı Ara';
require_once 'includes/header.php';

$query = trim($_GET['q'] ?? '');
$results = [];

if ($query) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username LIKE ? OR display_name LIKE ? OR email LIKE ?) AND id != ? LIMIT 30");
    $like = '%' . $query . '%';
    $stmt->execute([$like, $like, $like, $currentUser['id']]);
    $results = $stmt->fetchAll();
}
?>

<h1 class="page-title">
    <span class="icon"><i class="fas fa-user-search"></i></span>
    Kullanıcı Ara
</h1>

<form method="GET" class="search-bar">
    <input type="text" name="q" value="<?= e($query) ?>" placeholder="İsim veya kullanıcı adı ile arayın...">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Ara</button>
</form>

<?php if ($query && empty($results)): ?>
    <div class="empty-state">
        <div class="icon"><i class="fas fa-search"></i></div>
        <h3>Sonuç bulunamadı</h3>
        <p>"<?= e($query) ?>" ile eşleşen kullanıcı bulunamadı.</p>
    </div>
<?php endif; ?>

<?php foreach ($results as $user): ?>
    <?php
    $friendStatus = friendRequestExists($pdo, $currentUser['id'], $user['id']);
    ?>
    <div class="user-card">
        <a href="<?= BASE_URL ?>/profile.php?id=<?= $user['id'] ?>">
            <img src="<?= avatar($user['avatar']) ?>" alt="">
        </a>
        <div class="user-card-info">
            <h4><a href="<?= BASE_URL ?>/profile.php?id=<?= $user['id'] ?>"><?= e($user['display_name'] ?: $user['username']) ?></a></h4>
            <span>@<?= e($user['username']) ?></span>
        </div>
        <div style="display:flex;gap:8px;">
            <?php if (!$friendStatus): ?>
                <button class="btn btn-primary btn-sm" onclick="friendAction('add', <?= $user['id'] ?>)"><i class="fas fa-user-plus"></i> Arkadaş Ekle</button>
            <?php elseif ($friendStatus['status'] === 'accepted'): ?>
                <span class="pending-badge approved"><i class="fas fa-check"></i> Arkadaş</span>
            <?php elseif ($friendStatus['status'] === 'pending'): ?>
                <span class="pending-badge pending"><i class="fas fa-clock"></i> Bekliyor</span>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/messages.php?user=<?= $user['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-envelope"></i></a>
        </div>
    </div>
<?php endforeach; ?>

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
