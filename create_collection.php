<?php
$pageTitle = 'Koleksiyon Oluştur';
require_once 'includes/header.php';

$categories = ['Yemek Tarifleri', 'Ev & Yaşam', 'Teknoloji', 'Kariyer', 'Alışveriş', 'Sağlık', 'Eğitim', 'El İşi', 'Bahçe', 'Genel'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'Genel';
    $hashtags = trim($_POST['hashtags'] ?? '');

    if (!$title) {
        $error = 'Koleksiyon adı boş olamaz.';
    } else {
        $coverPath = 'default_cover.png';
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $uploaded = uploadImage($_FILES['cover'], 'covers');
            if ($uploaded) $coverPath = $uploaded;
        }

        $stmt = $pdo->prepare("INSERT INTO collections (user_id, title, description, category, cover_image, hashtags) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$currentUser['id'], $title, $description, $category, $coverPath, $hashtags]);
        $newId = $pdo->lastInsertId();
        redirect('collection.php?id=' . $newId);
    }
}
?>

<h1 class="page-title">
    <span class="icon"><i class="fas fa-plus-circle"></i></span>
    Yeni Koleksiyon Oluştur
</h1>

<div class="card" style="max-width:650px;">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Koleksiyon Adı</label>
            <input type="text" id="title" name="title" class="form-control" placeholder="Örn: En İyi Ev Yemek Tarifleri" required>
        </div>
        <div class="form-group">
            <label for="description">Açıklama</label>
            <textarea id="description" name="description" class="form-control" placeholder="Bu koleksiyon ne hakkında?"></textarea>
        </div>
        <div class="form-group">
            <label for="category">Kategori</label>
            <select id="category" name="category" class="form-control">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="hashtags">Etiketler (virgülle ayırın)</label>
            <input type="text" id="hashtags" name="hashtags" class="form-control" placeholder="yemek, tarif, kolay">
        </div>
        <div class="form-group">
            <label>Kapak Görseli</label>
            <div class="file-input-wrapper" style="display:block;">
                <span class="file-input-label" style="width:100%;justify-content:center;">
                    <i class="fas fa-image"></i> Görsel Seç
                </span>
                <input type="file" name="cover" accept="image/*">
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-plus"></i> Koleksiyonu Oluştur
        </button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
