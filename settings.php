<?php
$pageTitle = 'Ayarlar';
require_once 'includes/header.php';

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $theme = in_array($_POST['theme'], ['light', 'dark']) ? $_POST['theme'] : 'light';
    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute([$theme, $currentUser['id']]);
    $currentUser['theme'] = $theme;
    $success = 'Ayarlar kaydedildi!';
}
?>

<h1 class="page-title">
    <span class="icon"><i class="fas fa-cog"></i></span>
    Ayarlar
</h1>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<div class="settings-section">
    <h3><i class="fas fa-palette"></i> Tema Ayarları</h3>
    <form method="POST">
        <div class="theme-options">
            <label class="theme-option <?= ($currentUser['theme'] ?? 'light') === 'light' ? 'active' : '' ?>" onclick="selectTheme('light', this)">
                <div class="theme-preview light-preview"></div>
                <span>☀️ Açık Tema</span>
                <input type="radio" name="theme" value="light" <?= ($currentUser['theme'] ?? 'light') === 'light' ? 'checked' : '' ?> style="display:none;">
            </label>
            <label class="theme-option <?= ($currentUser['theme'] ?? 'light') === 'dark' ? 'active' : '' ?>" onclick="selectTheme('dark', this)">
                <div class="theme-preview dark-preview"></div>
                <span>🌙 Koyu Tema</span>
                <input type="radio" name="theme" value="dark" <?= ($currentUser['theme'] ?? 'light') === 'dark' ? 'checked' : '' ?> style="display:none;">
            </label>
        </div>
        <br>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Kaydet</button>
    </form>
</div>

<div class="settings-section">
    <h3><i class="fas fa-user-edit"></i> Profil Ayarları</h3>
    <p style="color:var(--text-light);margin-bottom:12px;">Profil bilgilerinizi düzenlemek için:</p>
    <a href="<?= BASE_URL ?>/edit_profile.php" class="btn btn-secondary"><i class="fas fa-edit"></i> Profili Düzenle</a>
</div>

<script>
function selectTheme(theme, el) {
    document.querySelectorAll('.theme-option').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('bp_theme', theme);
    document.getElementById('themeBtn').innerHTML = '<i class="fas fa-' + (theme === 'dark' ? 'sun' : 'moon') + '"></i>';
}
</script>

<?php require_once 'includes/footer.php'; ?>
