<?php
$pageTitle = 'Kayıtlı Koleksiyonlar';
require_once 'includes/header.php';

$stmt = $pdo->prepare("SELECT c.*, u.username, u.display_name, u.avatar,
    (SELECT COUNT(*) FROM collection_items WHERE collection_id = c.id AND status = 'approved') as item_count,
    (SELECT COUNT(*) FROM collection_followers WHERE collection_id = c.id) as follower_count
    FROM collection_followers cf
    JOIN collections c ON cf.collection_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE cf.user_id = ?
    ORDER BY cf.created_at DESC");
$stmt->execute([$currentUser['id']]);
$saved = $stmt->fetchAll();
?>

<h1 class="page-title">
    <span class="icon"><i class="fas fa-bookmark"></i></span>
    Kayıtlı Koleksiyonlar
</h1>

<?php if (empty($saved)): ?>
    <div class="empty-state">
        <div class="icon"><i class="fas fa-bookmark"></i></div>
        <h3>Kayıtlı koleksiyonunuz yok</h3>
        <p>Beğendiğiniz koleksiyonları takip ederek burada görebilirsiniz.</p>
        <br>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary"><i class="fas fa-compass"></i> Keşfet</a>
    </div>
<?php else: ?>
    <div class="collection-grid">
        <?php foreach ($saved as $col): ?>
            <a href="<?= BASE_URL ?>/collection.php?id=<?= $col['id'] ?>" class="collection-card" style="text-decoration:none;color:inherit;">
                <div class="collection-card-cover">
                    <img src="<?= coverImage($col['cover_image']) ?>" alt="<?= e($col['title']) ?>">
                    <span class="collection-card-category"><?= e($col['category']) ?></span>
                </div>
                <div class="collection-card-body">
                    <h3><?= e($col['title']) ?></h3>
                    <p><?= e($col['description']) ?></p>
                    <?php if ($col['hashtags']): ?>
                        <div class="collection-card-tags">
                            <?php foreach (explode(',', $col['hashtags']) as $tag): ?>
                                <span class="tag">#<?= e(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="collection-card-meta">
                        <div class="author">
                            <img src="<?= avatar($col['avatar']) ?>" alt="">
                            <?= e($col['display_name'] ?: $col['username']) ?>
                        </div>
                        <span><i class="fas fa-layer-group"></i> <?= $col['item_count'] ?> içerik</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
