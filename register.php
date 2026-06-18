<?php
require_once 'config.php';
require_once 'helpers.php';

if (isLoggedIn()) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (!$email || !$password || !$password_confirm) {
        $error = 'Tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($password !== $password_confirm) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Bu e-posta zaten kayıtlı.';
        } else {
            $username = explode('@', $email)[0] . rand(100, 999);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, username, display_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$email, $hash, $username, $username]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            redirect('edit_profile.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1><i class="fas fa-lightbulb"></i> BilgiPaylaş</h1>
        <p class="subtitle">Bilgi paylaşarak büyüyün</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">E-posta Adresi</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="ornek@email.com" value="<?= e($email ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="En az 6 karakter" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Şifre Tekrar</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Şifrenizi tekrar girin" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Kayıt Ol</button>
        </form>
        <div class="auth-footer">
            Zaten hesabınız var mı? <a href="<?= BASE_URL ?>/login.php">Giriş Yap</a>
        </div>
    </div>
</div>
</body>
</html>
