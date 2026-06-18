<?php
$pageTitle = 'Koleksiyonlarım';
require_once 'includes/header.php';

$stmt = $pdo->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM collection_items WHERE collection_id = c.id AND status = 'approved') as item_count,
    (SELECT COUNT(*) FROM collection_followers WHERE collection_id = c.id) as follower_count,
    (SELECT COUNT(*) FROM collection_items WHERE collection_id = c.id AND status = 'pending') as pending_count
    FROM collections c WHERE c.user_id = ? ORDER BY c.created_at DESC");
$stmt->execute([$currentUser['id']]);
$collections = $stmt->fetchAll();
?>

<h1 class="page-title">
    <span class="icon"><i class="fas fa-folder"></i></span>
    Koleksiyonlarım
</h1>

<?php if (empty($collections)): ?>
    <div class="empty-state">
        <div class="icon"><i class="fas fa-folder-open"></i></div>
        <h3>Henüz koleksiyonunuz yok</h3>
        <p>İlk koleksiyonunuzu oluşturup bilgilerinizi paylaşın!</p>
        <br>
        <a href="<?= BASE_URL ?>/create_collection.php" class="btn btn-primary"><i class="fas fa-plus"></i> Koleksiyon Oluştur</a>
    </div>
<?php else: ?>
    <div class="collection-grid">
        <?php foreach ($collections as $col): ?>
            <a href="<?= BASE_URL ?>/collection.php?id=<?= $col['id'] ?>" class="collection-card" style="text-decoration:none;color:inherit;">
                <div class="collection-card-cover">
                    <img src="<?= coverImage($col['cover_image']) ?>" alt="">
                    <span class="collection-card-category"><?= e($col['category']) ?></span>
                </div>
                <div class="collection-card-body">
                    <h3><?= e($col['title']) ?>
                        <?php if ($col['pending_count'] > 0): ?>
                            <span class="pending-badge pending" style="margin-left:8px;font-size:0.7rem;"><?= $col['pending_count'] ?> bekleyen</span>
                        <?php endif; ?>
                    </h3>
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

<?php require_once 'includes/footer.php'; ?>
