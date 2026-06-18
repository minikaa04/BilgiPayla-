<?php
$pageTitle = 'Profili Düzenle';
require_once 'config.php';
require_once 'helpers.php';
requireLogin();

$user = getCurrentUser($pdo);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = trim($_POST['display_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (!$display_name) {
        $error = 'Görünen ad boş bırakılamaz.';
    } else {
        $avatarPath = $user['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploaded = uploadImage($_FILES['avatar'], 'avatars');
            if ($uploaded) $avatarPath = $uploaded;
        }

        $stmt = $pdo->prepare("UPDATE users SET display_name = ?, bio = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$display_name, $bio, $avatarPath, $user['id']]);
        $success = 'Profil başarıyla güncellendi!';
        $user = getCurrentUser($pdo);
    }
}

require_once 'includes/header.php';
?>

<h1 class="page-title">
    <span class="icon"><i class="fas fa-edit"></i></span>
    Profili Düzenle
</h1>

<div class="card" style="max-width:600px;">
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group" style="text-align:center;">
            <img src="<?= avatar($user['avatar']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:12px;" alt="avatar">
            <div class="file-input-wrapper">
                <span class="file-input-label">
                    <i class="fas fa-camera"></i> Fotoğraf Değiştir
                </span>
                <input type="file" name="avatar" accept="image/*">
            </div>
        </div>
        <div class="form-group">
            <label for="display_name">Görünen Ad</label>
            <input type="text" id="display_name" name="display_name" class="form-control" value="<?= e($user['display_name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="bio">Hakkımda</label>
            <textarea id="bio" name="bio" class="form-control" placeholder="Kendinizi kısaca tanıtın..."><?= e($user['bio'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-save"></i> Kaydet
        </button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
