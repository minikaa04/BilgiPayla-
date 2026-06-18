<?php
$pageTitle = 'Ana Sayfa';
require_once 'includes/header.php';

// Get selected category
$category = $_GET['category'] ?? '';

// Fetch collections
if ($category) {
    $stmt = $pdo->prepare("SELECT c.*, u.username, u.display_name, u.avatar,
        (SELECT COUNT(*) FROM collection_items WHERE collection_id = c.id AND status = 'approved') as item_count,
        (SELECT COUNT(*) FROM collection_followers WHERE collection_id = c.id) as follower_count
        FROM collections c JOIN users u ON c.user_id = u.id
        WHERE c.category = ?
        ORDER BY c.created_at DESC LIMIT 50");
    $stmt->execute([$category]);
} else {
    $stmt = $pdo->query("SELECT c.*, u.username, u.display_name, u.avatar,
        (SELECT COUNT(*) FROM collection_items WHERE collection_id = c.id AND status = 'approved') as item_count,
        (SELECT COUNT(*) FROM collection_followers WHERE collection_id = c.id) as follower_count
        FROM collections c JOIN users u ON c.user_id = u.id
        ORDER BY c.created_at DESC LIMIT 50");
}
$collections = $stmt->fetchAll();

// Fetch active ads
$stmt = $pdo->query("SELECT * FROM ads WHERE is_active = 1 ORDER BY RAND() LIMIT 2");
$ads = $stmt->fetchAll();

$categories = ['Yemek Tarifleri', 'Ev & Yaşam', 'Teknoloji', 'Kariyer', 'Alışveriş', 'Sağlık', 'Eğitim', 'El İşi', 'Bahçe', 'Genel'];
?>

<h1 class="page-title">
    <span class="icon"><i class="fas fa-compass"></i></span>
    Keşfet
</h1>

<!-- Categories -->
<div class="categories-bar">
    <a href="<?= BASE_URL ?>/index.php" class="category-pill <?= !$category ? 'active' : '' ?>">Tümü</a>
    <?php foreach ($categories as $cat): ?>
        <a href="<?= BASE_URL ?>/index.php?category=<?= urlencode($cat) ?>" class="category-pill <?= $category === $cat ? 'active' : '' ?>"><?= e($cat) ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($collections)): ?>
    <div class="empty-state">
        <div class="icon"><i class="fas fa-folder-open"></i></div>
        <h3>Henüz koleksiyon yok</h3>
        <p>İlk koleksiyonu siz oluşturun ve bilgilerinizi paylaşın!</p>
        <br>
        <a href="<?= BASE_URL ?>/create_collection.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Koleksiyon Oluştur
        </a>
    </div>
<?php else: ?>
    <div class="collection-grid">
        <?php foreach ($collections as $index => $col): ?>
            <!-- Show ad after every 3 collections -->
            <?php if ($index > 0 && $index % 3 === 0 && !empty($ads)): 
                $ad = $ads[($index/3 - 1) % count($ads)]; 
            ?>
                <div class="ad-banner" style="grid-column: 1 / -1; margin-bottom: 20px;">
                    <?php if ($ad['image']): ?>
                        <img src="<?= BASE_URL ?>/uploads/<?= e($ad['image']) ?>" alt="">
                    <?php endif; ?>
                    <div class="ad-banner-content">
                        <h4><?= e($ad['title']) ?></h4>
                        <p><?= e($ad['content']) ?></p>
                        <?php if ($ad['link']): ?>
                            <a href="<?= e($ad['link']) ?>" target="_blank" class="btn btn-primary btn-sm" style="margin-top:8px;">Detaylar</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

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
                        <span><i class="fas fa-layer-group"></i> <?= $col['item_count'] ?> içerik · <i class="fas fa-users"></i> <?= $col['follower_count'] ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
