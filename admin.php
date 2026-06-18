<?php
$pageTitle = 'Yönetim Paneli';
require_once 'includes/header.php';
requireAdmin($currentUser);

$tab = $_GET['tab'] ?? 'dashboard';

// Stats
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$collectionCount = $pdo->query("SELECT COUNT(*) FROM collections")->fetchColumn();
$itemCount = $pdo->query("SELECT COUNT(*) FROM collection_items")->fetchColumn();
$messageCount = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'block_item') {
        $itemId = (int)$_POST['item_id'];
        $pdo->prepare("UPDATE collection_items SET is_blocked = 1 WHERE id = ?")->execute([$itemId]);
        $success = 'İçerik engellendi.';
    } elseif ($action === 'unblock_item') {
        $itemId = (int)$_POST['item_id'];
        $pdo->prepare("UPDATE collection_items SET is_blocked = 0 WHERE id = ?")->execute([$itemId]);
        $success = 'İçerik engeli kaldırıldı.';
    } elseif ($action === 'delete_item') {
        $itemId = (int)$_POST['item_id'];
        $pdo->prepare("DELETE FROM collection_items WHERE id = ?")->execute([$itemId]);
        $success = 'İçerik silindi.';
    } elseif ($action === 'delete_collection') {
        $colId = (int)$_POST['collection_id'];
        $pdo->prepare("DELETE FROM collections WHERE id = ?")->execute([$colId]);
        $success = 'Koleksiyon silindi.';
    } elseif ($action === 'create_ad') {
        $adTitle = trim($_POST['ad_title'] ?? '');
        $adContent = trim($_POST['ad_content'] ?? '');
        $adLink = trim($_POST['ad_link'] ?? '');
        $adImage = null;
        if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
            $adImage = uploadImage($_FILES['ad_image'], 'ads');
        }
        if ($adTitle) {
            $pdo->prepare("INSERT INTO ads (title, content, image, link) VALUES (?, ?, ?, ?)")->execute([$adTitle, $adContent, $adImage, $adLink]);
            $success = 'Reklam oluşturuldu.';
        }
    } elseif ($action === 'delete_ad') {
        $adId = (int)$_POST['ad_id'];
        $pdo->prepare("DELETE FROM ads WHERE id = ?")->execute([$adId]);
        $success = 'Reklam silindi.';
    } elseif ($action === 'toggle_ad') {
        $adId = (int)$_POST['ad_id'];
        $pdo->prepare("UPDATE ads SET is_active = NOT is_active WHERE id = ?")->execute([$adId]);
        $success = 'Reklam durumu güncellendi.';
    }
}
?>

<h1 class="page-title" style="color:var(--danger);">
    <span class="icon"><i class="fas fa-shield-alt"></i></span>
    Yönetim Paneli
</h1>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<!-- Tabs -->
<div class="tabs">
    <a href="?tab=dashboard" class="tab <?= $tab === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> Gösterge</a>
    <a href="?tab=content" class="tab <?= $tab === 'content' ? 'active' : '' ?>"><i class="fas fa-layer-group"></i> İçerikler</a>
    <a href="?tab=collections" class="tab <?= $tab === 'collections' ? 'active' : '' ?>"><i class="fas fa-folder"></i> Koleksiyonlar</a>
    <a href="?tab=users" class="tab <?= $tab === 'users' ? 'active' : '' ?>"><i class="fas fa-users"></i> Kullanıcılar</a>
    <a href="?tab=ads" class="tab <?= $tab === 'ads' ? 'active' : '' ?>"><i class="fas fa-ad"></i> Reklamlar</a>
</div>

<?php if ($tab === 'dashboard'): ?>
    <div class="admin-stats">
        <div class="admin-stat-card">
            <div class="stat-num"><?= $userCount ?></div>
            <div class="stat-label">Kullanıcı</div>
        </div>
        <div class="admin-stat-card">
            <div class="stat-num"><?= $collectionCount ?></div>
            <div class="stat-label">Koleksiyon</div>
        </div>
        <div class="admin-stat-card">
            <div class="stat-num"><?= $itemCount ?></div>
            <div class="stat-label">İçerik</div>
        </div>
        <div class="admin-stat-card">
            <div class="stat-num"><?= $messageCount ?></div>
            <div class="stat-label">Mesaj</div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-bottom:12px;"><i class="fas fa-clock"></i> Son Eklenen İçerikler</h3>
        <?php
        $stmt = $pdo->query("SELECT ci.*, u.display_name, u.username, c.title as col_title FROM collection_items ci JOIN users u ON ci.user_id = u.id JOIN collections c ON ci.collection_id = c.id ORDER BY ci.created_at DESC LIMIT 10");
        $recentItems = $stmt->fetchAll();
        ?>
        <table class="admin-table">
            <thead><tr><th>İçerik</th><th>Koleksiyon</th><th>Yazar</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($recentItems as $item): ?>
                <tr>
                    <td><?= e(mb_substr($item['title'], 0, 40)) ?></td>
                    <td><?= e(mb_substr($item['col_title'], 0, 30)) ?></td>
                    <td><?= e($item['display_name'] ?: $item['username']) ?></td>
                    <td>
                        <?php if ($item['is_blocked']): ?>
                            <span class="pending-badge rejected"><i class="fas fa-ban"></i> Engelli</span>
                        <?php else: ?>
                            <span class="pending-badge <?= $item['status'] ?>"><?= e($item['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <?php if ($item['is_blocked']): ?>
                                <button type="submit" name="action" value="unblock_item" class="btn btn-success btn-sm"><i class="fas fa-check"></i></button>
                            <?php else: ?>
                                <button type="submit" name="action" value="block_item" class="btn btn-danger btn-sm" title="Engelle"><i class="fas fa-ban"></i></button>
                            <?php endif; ?>
                            <button type="submit" name="action" value="delete_item" class="btn btn-danger btn-sm" title="Sil" onclick="return confirm('Bu içeriği silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($tab === 'content'): ?>
    <div class="card">
        <h3 style="margin-bottom:12px;"><i class="fas fa-layer-group"></i> Tüm İçerikler</h3>
        <?php
        $stmt = $pdo->query("SELECT ci.*, u.display_name, u.username, c.title as col_title FROM collection_items ci JOIN users u ON ci.user_id = u.id JOIN collections c ON ci.collection_id = c.id ORDER BY ci.created_at DESC LIMIT 50");
        $items = $stmt->fetchAll();
        ?>
        <table class="admin-table">
            <thead><tr><th>İçerik</th><th>Koleksiyon</th><th>Yazar</th><th>Beğeni</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e(mb_substr($item['title'], 0, 40)) ?></td>
                    <td><a href="<?= BASE_URL ?>/collection.php?id=<?= $item['collection_id'] ?>"><?= e(mb_substr($item['col_title'], 0, 25)) ?></a></td>
                    <td><?= e($item['display_name'] ?: $item['username']) ?></td>
                    <td>👍 <?= $item['likes'] ?> / 👎 <?= $item['dislikes'] ?></td>
                    <td>
                        <?php if ($item['is_blocked']): ?>
                            <span class="pending-badge rejected"><i class="fas fa-ban"></i> Engelli</span>
                        <?php else: ?>
                            <span class="pending-badge <?= $item['status'] ?>"><?= e($item['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <?php if ($item['is_blocked']): ?>
                                <button type="submit" name="action" value="unblock_item" class="btn btn-success btn-sm"><i class="fas fa-check"></i></button>
                            <?php else: ?>
                                <button type="submit" name="action" value="block_item" class="btn btn-danger btn-sm"><i class="fas fa-ban"></i></button>
                            <?php endif; ?>
                            <button type="submit" name="action" value="delete_item" class="btn btn-danger btn-sm" onclick="return confirm('Sil?')"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($tab === 'collections'): ?>
    <div class="card">
        <h3 style="margin-bottom:12px;"><i class="fas fa-folder"></i> Tüm Koleksiyonlar</h3>
        <?php
        $stmt = $pdo->query("SELECT c.*, u.display_name, u.username, 
            (SELECT COUNT(*) FROM collection_items WHERE collection_id = c.id) as item_count
            FROM collections c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC");
        $cols = $stmt->fetchAll();
        ?>
        <table class="admin-table">
            <thead><tr><th>Koleksiyon</th><th>Kategori</th><th>Oluşturan</th><th>İçerik</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($cols as $col): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/collection.php?id=<?= $col['id'] ?>"><?= e($col['title']) ?></a></td>
                    <td><?= e($col['category']) ?></td>
                    <td><?= e($col['display_name'] ?: $col['username']) ?></td>
                    <td><?= $col['item_count'] ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="collection_id" value="<?= $col['id'] ?>">
                            <button type="submit" name="action" value="delete_collection" class="btn btn-danger btn-sm" onclick="return confirm('Bu koleksiyonu ve tüm içeriklerini silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i> Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($tab === 'users'): ?>
    <div class="card">
        <h3 style="margin-bottom:12px;"><i class="fas fa-users"></i> Kullanıcılar</h3>
        <?php $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC"); $users = $stmt->fetchAll(); ?>
        <table class="admin-table">
            <thead><tr><th>Avatar</th><th>Ad</th><th>Email</th><th>Rol</th><th>Kayıt</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><img src="<?= avatar($u['avatar']) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt=""></td>
                    <td><a href="<?= BASE_URL ?>/profile.php?id=<?= $u['id'] ?>"><?= e($u['display_name'] ?: $u['username']) ?></a></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="admin-badge <?= $u['role'] === 'admin' ? 'admin' : '' ?>"><?= $u['role'] === 'admin' ? '🛡️ Yönetici' : '👤 Kullanıcı' ?></span></td>
                    <td><?= timeAgo($u['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($tab === 'ads'): ?>
    <!-- Create Ad Form -->
    <div class="card">
        <h3 style="margin-bottom:16px;"><i class="fas fa-plus-circle"></i> Yeni Reklam Oluştur</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_ad">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Reklam Başlığı</label>
                    <input type="text" name="ad_title" class="form-control" placeholder="Reklam başlığı" required>
                </div>
                <div class="form-group">
                    <label>Link (İsteğe bağlı)</label>
                    <input type="text" name="ad_link" class="form-control" placeholder="https://...">
                </div>
            </div>
            <div class="form-group">
                <label>İçerik</label>
                <textarea name="ad_content" class="form-control" placeholder="Reklam açıklaması"></textarea>
            </div>
            <div class="form-group">
                <label>Görsel</label>
                <div class="file-input-wrapper" style="display:block;">
                    <span class="file-input-label" style="width:100%;justify-content:center;"><i class="fas fa-image"></i> Görsel Seç</span>
                    <input type="file" name="ad_image" accept="image/*">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Reklam Oluştur</button>
        </form>
    </div>

    <!-- Existing Ads -->
    <div class="card">
        <h3 style="margin-bottom:12px;"><i class="fas fa-ad"></i> Mevcut Reklamlar</h3>
        <?php $stmt = $pdo->query("SELECT * FROM ads ORDER BY created_at DESC"); $ads = $stmt->fetchAll(); ?>
        <?php if (empty($ads)): ?>
            <div class="empty-state" style="padding:20px;"><p>Henüz reklam yok</p></div>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Başlık</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($ads as $ad): ?>
                    <tr>
                        <td><?= e($ad['title']) ?></td>
                        <td>
                            <?php if ($ad['is_active']): ?>
                                <span class="pending-badge approved"><i class="fas fa-check"></i> Aktif</span>
                            <?php else: ?>
                                <span class="pending-badge pending"><i class="fas fa-pause"></i> Pasif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= timeAgo($ad['created_at']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
                                <button type="submit" name="action" value="toggle_ad" class="btn btn-secondary btn-sm"><i class="fas fa-toggle-<?= $ad['is_active'] ? 'on' : 'off' ?>"></i></button>
                                <button type="submit" name="action" value="delete_ad" class="btn btn-danger btn-sm" onclick="return confirm('Sil?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
