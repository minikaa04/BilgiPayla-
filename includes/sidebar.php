<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-section-title">Menü</div>
        <a href="<?= BASE_URL ?>/index.php" class="sidebar-link <?= $currentPage === 'index' ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-home"></i></span>
            Ana Sayfa
        </a>
        <a href="<?= BASE_URL ?>/profile.php?id=<?= $currentUser['id'] ?>" class="sidebar-link <?= $currentPage === 'profile' && isset($_GET['id']) && $_GET['id'] == $currentUser['id'] ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-user"></i></span>
            Profilim
        </a>
        <a href="<?= BASE_URL ?>/messages.php" class="sidebar-link <?= $currentPage === 'messages' ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-envelope"></i></span>
            Kişisel Mesajlar
            <?php if($unreadMessages > 0): ?>
                <span class="badge-count"><?= $unreadMessages ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/friends.php" class="sidebar-link <?= $currentPage === 'friends' ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-user-friends"></i></span>
            Arkadaşlar
            <?php if($pendingFriends > 0): ?>
                <span class="badge-count"><?= $pendingFriends ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/notifications.php" class="sidebar-link <?= $currentPage === 'notifications' ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-bell"></i></span>
            Bildirimler
            <?php if($unreadNotifications > 0): ?>
                <span class="badge-count"><?= $unreadNotifications ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-title">Arama</div>
        <a href="<?= BASE_URL ?>/search_users.php" class="sidebar-link <?= $currentPage === 'search_users' ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-user-search"></i></span>
            Kullanıcı Ara
        </a>
        <a href="<?= BASE_URL ?>/search_content.php" class="sidebar-link <?= $currentPage === 'search_content' ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-search"></i></span>
            İçerik Ara
        </a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-title">Koleksiyonlar</div>
        <a href="<?= BASE_URL ?>/create_collection.php" class="sidebar-link <?= $currentPage === 'create_collection' ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-plus-circle"></i></span>
            Koleksiyon Oluştur
        </a>
        <a href="<?= BASE_URL ?>/my_collections.php" class="sidebar-link <?= $currentPage === 'my_collections' ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-folder"></i></span>
            Koleksiyonlarım
        </a>
        <a href="<?= BASE_URL ?>/saved_collections.php" class="sidebar-link <?= $currentPage === 'saved_collections' ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-bookmark"></i></span>
            Kayıtlı Koleksiyonlar
        </a>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-section-title">Ayarlar</div>
        <a href="<?= BASE_URL ?>/settings.php" class="sidebar-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
            <span class="icon"><i class="fas fa-cog"></i></span>
            Ayarlar
        </a>
        <?php if ($isAdmin): ?>
        <a href="<?= BASE_URL ?>/admin.php" class="sidebar-link <?= $currentPage === 'admin' ? 'active' : '' ?>" style="color:var(--danger);">
            <span class="icon"><i class="fas fa-shield-alt"></i></span>
            Yönetim Paneli
        </a>
        <?php endif; ?>
    </div>
</aside>
