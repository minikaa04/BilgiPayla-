<?php
$pageTitle = 'İçerik Ara';
require_once 'includes/header.php';

$query = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$results = [];
$categories = ['Yemek Tarifleri', 'Ev & Yaşam', 'Teknoloji', 'Kariyer', 'Alışveriş', 'Sağlık', 'Eğitim', 'El İşi', 'Bahçe', 'Genel'];

if ($query || $category) {
    $sql = "SELECT c.*, u.username, u.display_name, u.avatar,
        (SELECT COUNT(*) FROM collection_items WHERE collection_id = c.id AND status = 'approved') as item_count,
        (SELECT COUNT(*) FROM collection_followers WHERE collection_id = c.id) as follower_count
        FROM collections c JOIN users u ON c.user_id = u.id WHERE 1=1";
    $params = [];

    if ($query) {
        $sql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.hashtags LIKE ?)";
        $like = '%' . $query . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($category) {
        $sql .= " AND c.category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY c.created_at DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}
?>

<h1 class="page-title">
    <span class="icon"><i class="fas fa-search"></i></span>
    İçerik Ara
</h1>

<form method="GET" class="search-bar">
    <input type="text" name="q" value="<?= e($query) ?>" placeholder="Etiket, başlık veya anahtar kelime ile arayın...">
    <select name="category" class="form-control" style="max-width:200px;">
        <option value="">Tüm Kategoriler</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Ara</button>
</form>

<?php if (($query || $category) && empty($results)): ?>
    <div class="empty-state">
        <div class="icon"><i class="fas fa-search"></i></div>
        <h3>Sonuç bulunamadı</h3>
        <p>Farklı anahtar kelimeler veya kategoriler deneyin.</p>
    </div>
<?php elseif (!empty($results)): ?>
    <div class="collection-grid">
        <?php foreach ($results as $col): ?>
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
                        <span><?= $col['item_count'] ?> içerik</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="categories-bar" style="flex-wrap:wrap;">
        <?php foreach ($categories as $cat): ?>
            <a href="?category=<?= urlencode($cat) ?>" class="category-pill"><?= e($cat) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="empty-state">
        <div class="icon"><i class="fas fa-compass"></i></div>
        <h3>Koleksiyonları Keşfedin</h3>
        <p>Etiketler, anahtar kelimeler veya kategoriler ile arama yapın.</p>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
