<?php
require_once 'config.php';
require_once 'helpers.php';

if (isLoggedIn()) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Tüm alanları doldurun.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            redirect('index.php');
        } else {
            $error = 'E-posta veya şifre hatalı.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1><i class="fas fa-lightbulb"></i> BilgiPaylaş</h1>
        <p class="subtitle">Hesabınıza giriş yapın</p>

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
                <input type="password" id="password" name="password" class="form-control" placeholder="Şifrenizi girin" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Giriş Yap</button>
        </form>
        <div class="auth-footer">
            Hesabınız yok mu? <a href="<?= BASE_URL ?>/register.php">Kayıt Ol</a>
        </div>
    </div>
</div>
</body>
</html>
