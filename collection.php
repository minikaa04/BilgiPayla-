<?php
require_once 'config.php';
require_once 'helpers.php';
requireLogin();

$collectionId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT c.*, u.username, u.display_name, u.avatar FROM collections c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
$stmt->execute([$collectionId]);
$collection = $stmt->fetch();

if (!$collection) redirect('index.php');

$isOwner = ($collection['user_id'] == $_SESSION['user_id']);
$currentUser = getCurrentUser($pdo);
$tab = $_GET['tab'] ?? 'items';

// Handle contribution submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contribute'])) {
    $itemTitle = trim($_POST['item_title'] ?? '');
    $itemContent = trim($_POST['item_content'] ?? '');

    if ($itemTitle && $itemContent) {
        $imagePath = null;
        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = uploadImage($_FILES['item_image'], 'items');
        }

        $status = $isOwner ? 'approved' : 'pending';
        $stmt = $pdo->prepare("INSERT INTO collection_items (collection_id, user_id, title, content, image, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$collectionId, $currentUser['id'], $itemTitle, $itemContent, $imagePath, $status]);

        if ($isOwner) {
            // Notify followers about new content
            $msg = $currentUser['display_name'] . ' "' . $collection['title'] . '" koleksiyonuna yeni içerik ekledi: ' . $itemTitle;
            notifyCollectionFollowers($pdo, $collectionId, 'new_item', $msg, 'collection.php?id=' . $collectionId, $currentUser['id']);
        } else {
            // Notify collection owner about pending contribution
            $msg = $currentUser['display_name'] . ' "' . $collection['title'] . '" koleksiyonunuza katkıda bulunmak istiyor.';
            createNotification($pdo, $collection['user_id'], 'contribution', $msg, 'collection.php?id=' . $collectionId . '&tab=pending');
        }
    }
    header('Location: ' . BASE_URL . '/collection.php?id=' . $collectionId);
    exit;
}

// Get approved items (excluding blocked)
$stmt = $pdo->prepare("SELECT ci.*, u.username, u.display_name, u.avatar FROM collection_items ci JOIN users u ON ci.user_id = u.id WHERE ci.collection_id = ? AND ci.status = 'approved' AND ci.is_blocked = 0 ORDER BY ci.likes DESC, ci.created_at DESC");
$stmt->execute([$collectionId]);
$approvedItems = $stmt->fetchAll();

// Get pending items (for owner)
$pendingItems = [];
if ($isOwner) {
    $stmt = $pdo->prepare("SELECT ci.*, u.username, u.display_name, u.avatar FROM collection_items ci JOIN users u ON ci.user_id = u.id WHERE ci.collection_id = ? AND ci.status = 'pending' ORDER BY ci.created_at DESC");
    $stmt->execute([$collectionId]);
    $pendingItems = $stmt->fetchAll();
}

// Check follow status
$stmt = $pdo->prepare("SELECT id FROM collection_followers WHERE collection_id = ? AND user_id = ?");
$stmt->execute([$collectionId, $currentUser['id']]);
$isFollowing = $stmt->fetch();

// Get user votes for items
$userVotes = [];
$itemIds = array_column($approvedItems, 'id');
if (!empty($itemIds)) {
    $placeholders = str_repeat('?,', count($itemIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT item_id, vote FROM item_votes WHERE user_id = ? AND item_id IN ($placeholders)");
    $stmt->execute(array_merge([$currentUser['id']], $itemIds));
    while ($row = $stmt->fetch()) {
        $userVotes[$row['item_id']] = $row['vote'];
    }
}

// Follower count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM collection_followers WHERE collection_id = ?");
$stmt->execute([$collectionId]);
$followerCount = $stmt->fetchColumn();

$pageTitle = $collection['title'];
require_once 'includes/header.php';
?>

<!-- Collection Header -->
<div class="card" style="overflow:hidden;padding:0;">
    <div style="height:200px;overflow:hidden;position:relative;">
        <img src="<?= coverImage($collection['cover_image']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
        <span class="collection-card-category" style="position:absolute;top:16px;left:16px;"><?= e($collection['category']) ?></span>
    </div>
    <div style="padding:24px;">
        <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:8px;"><?= e($collection['title']) ?></h1>
        <p style="color:var(--text-light);margin-bottom:16px;"><?= e($collection['description']) ?></p>

        <?php if ($collection['hashtags']): ?>
            <div class="collection-card-tags" style="margin-bottom:16px;">
                <?php foreach (explode(',', $collection['hashtags']) as $tag): ?>
                    <span class="tag">#<?= e(trim($tag)) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div class="card-header" style="margin:0;">
                <a href="<?= BASE_URL ?>/profile.php?id=<?= $collection['user_id'] ?>">
                    <img src="<?= avatar($collection['avatar']) ?>" class="card-avatar" alt="">
                </a>
                <div class="card-user-info">
                    <h4><a href="<?= BASE_URL ?>/profile.php?id=<?= $collection['user_id'] ?>"><?= e($collection['display_name'] ?: $collection['username']) ?></a></h4>
                    <span><?= count($approvedItems) ?> içerik · <?= $followerCount ?> takipçi</span>
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <?php if (!$isOwner): ?>
                    <button class="btn <?= $isFollowing ? 'btn-secondary' : 'btn-primary' ?>" onclick="toggleFollow(<?= $collectionId ?>)">
                        <i class="fas fa-<?= $isFollowing ? 'check' : 'plus' ?>"></i> <?= $isFollowing ? 'Takip Ediliyor' : 'Takip Et' ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <a href="?id=<?= $collectionId ?>&tab=items" class="tab <?= $tab === 'items' ? 'active' : '' ?>">
        <i class="fas fa-layer-group"></i> İçerikler (<?= count($approvedItems) ?>)
    </a>
    <a href="?id=<?= $collectionId ?>&tab=contribute" class="tab <?= $tab === 'contribute' ? 'active' : '' ?>">
        <i class="fas fa-plus"></i> Katkıda Bulun
    </a>
    <?php if ($isOwner && count($pendingItems) > 0): ?>
        <a href="?id=<?= $collectionId ?>&tab=pending" class="tab <?= $tab === 'pending' ? 'active' : '' ?>">
            <i class="fas fa-clock"></i> Bekleyen (<?= count($pendingItems) ?>)
        </a>
    <?php endif; ?>
</div>

<?php if ($tab === 'items'): ?>
    <?php if (empty($approvedItems)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-layer-group"></i></div>
            <h3>Henüz içerik yok</h3>
            <p>İlk katkıyı siz yapın!</p>
        </div>
    <?php else: ?>
        <?php foreach ($approvedItems as $item): ?>
            <div class="item-card">
                <div class="card-header">
                    <a href="<?= BASE_URL ?>/profile.php?id=<?= $item['user_id'] ?>">
                        <img src="<?= avatar($item['avatar']) ?>" class="card-avatar" alt="">
                    </a>
                    <div class="card-user-info">
                        <h4><a href="<?= BASE_URL ?>/profile.php?id=<?= $item['user_id'] ?>"><?= e($item['display_name'] ?: $item['username']) ?></a></h4>
                        <span><?= timeAgo($item['created_at']) ?></span>
                    </div>
                </div>
                <h4><?= e($item['title']) ?></h4>
                <p><?= nl2br(e($item['content'])) ?></p>
                <?php if ($item['image']): ?>
                    <img src="<?= BASE_URL ?>/uploads/<?= e($item['image']) ?>" class="item-card-image" alt="">
                <?php endif; ?>
                <div class="item-actions">
                    <button class="vote-btn <?= isset($userVotes[$item['id']]) && $userVotes[$item['id']] == 1 ? 'liked' : '' ?>" onclick="vote(<?= $item['id'] ?>, 1, this)" id="like-<?= $item['id'] ?>">
                        <i class="fas fa-thumbs-up"></i> <span><?= $item['likes'] ?></span>
                    </button>
                    <button class="vote-btn <?= isset($userVotes[$item['id']]) && $userVotes[$item['id']] == -1 ? 'disliked' : '' ?>" onclick="vote(<?= $item['id'] ?>, -1, this)" id="dislike-<?= $item['id'] ?>">
                        <i class="fas fa-thumbs-down"></i> <span><?= $item['dislikes'] ?></span>
                    </button>
                    <button class="vote-btn" onclick="toggleComments(<?= $item['id'] ?>)">
                        <i class="fas fa-comment"></i> Yorumlar
                    </button>
                </div>

                <!-- Comments Section -->
                <div class="comments-section" id="comments-<?= $item['id'] ?>" style="display:none; margin-top:15px; padding-top:15px; border-top:1px solid var(--border);">
                    <div class="comment-list" id="comment-list-<?= $item['id'] ?>">
                        <?php
                        $stmtC = $pdo->prepare("SELECT ic.*, u.display_name, u.username, u.avatar FROM item_comments ic JOIN users u ON ic.user_id = u.id WHERE ic.item_id = ? ORDER BY ic.created_at ASC");
                        $stmtC->execute([$item['id']]);
                        $comments = $stmtC->fetchAll();
                        foreach ($comments as $comment):
                        ?>
                            <div class="comment" style="display:flex; gap:10px; margin-bottom:12px; font-size:0.85rem;">
                                <img src="<?= avatar($comment['avatar']) ?>" style="width:28px; height:28px; border-radius:50%;" alt="">
                                <div style="flex:1;">
                                    <div style="font-weight:600;"><?= e($comment['display_name'] ?: $comment['username']) ?> <span style="font-weight:400; color:var(--text-muted); font-size:0.75rem;"><?= timeAgo($comment['created_at']) ?></span></div>
                                    <p style="color:var(--text-light);"><?= e($comment['content']) ?></p>
                                </div>
                                <?php if ($isAdmin || $isOwner || $comment['user_id'] == $currentUser['id']): ?>
                                    <button class="btn btn-danger btn-sm" style="padding:2px 6px; height:fit-content;" onclick="deleteComment(<?= $comment['id'] ?>, this)"><i class="fas fa-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display:flex; gap:8px; margin-top:10px;">
                        <input type="text" class="form-control" placeholder="Yorum yaz..." id="comment-input-<?= $item['id'] ?>">
                        <button class="btn btn-primary btn-sm" onclick="addComment(<?= $item['id'] ?>)">Gönder</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php elseif ($tab === 'contribute'): ?>
    <div class="contribute-form">
        <h3><i class="fas fa-plus-circle"></i> Bilginizi Paylaşın</h3>
        <?php if (!$isOwner): ?>
            <p style="color:var(--text-light);font-size:0.85rem;margin-bottom:16px;">
                <i class="fas fa-info-circle"></i> Katkınız koleksiyon sahibinin onayından sonra yayınlanacaktır.
            </p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="contribute" value="1">
            <div class="form-group">
                <label for="item_title">Başlık</label>
                <input type="text" id="item_title" name="item_title" class="form-control" placeholder="Kısa ve açıklayıcı bir başlık" required>
            </div>
            <div class="form-group">
                <label for="item_content">İçerik</label>
                <textarea id="item_content" name="item_content" class="form-control" placeholder="Bilginizi detaylı olarak yazın..." style="min-height:150px;" required></textarea>
            </div>
            <div class="form-group">
                <label>Görsel (İsteğe bağlı)</label>
                <div class="file-input-wrapper" style="display:block;">
                    <span class="file-input-label" style="width:100%;justify-content:center;">
                        <i class="fas fa-image"></i> Görsel Ekle
                    </span>
                    <input type="file" name="item_image" accept="image/*">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-paper-plane"></i> Gönder
            </button>
        </form>
    </div>

<?php elseif ($tab === 'pending' && $isOwner): ?>
    <?php if (empty($pendingItems)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-inbox"></i></div>
            <h3>Bekleyen katkı yok</h3>
        </div>
    <?php else: ?>
        <?php foreach ($pendingItems as $item): ?>
            <div class="item-card" style="border-left:3px solid var(--warning);">
                <div class="card-header">
                    <a href="<?= BASE_URL ?>/profile.php?id=<?= $item['user_id'] ?>">
                        <img src="<?= avatar($item['avatar']) ?>" class="card-avatar" alt="">
                    </a>
                    <div class="card-user-info">
                        <h4><?= e($item['display_name'] ?: $item['username']) ?></h4>
                        <span><?= timeAgo($item['created_at']) ?></span>
                    </div>
                    <span class="pending-badge pending"><i class="fas fa-clock"></i> Bekliyor</span>
                </div>
                <h4><?= e($item['title']) ?></h4>
                <p><?= nl2br(e($item['content'])) ?></p>
                <?php if ($item['image']): ?>
                    <img src="<?= BASE_URL ?>/uploads/<?= e($item['image']) ?>" class="item-card-image" alt="">
                <?php endif; ?>
                <div class="item-actions" style="margin-top:12px;">
                    <button class="btn btn-success btn-sm" onclick="contributionAction(<?= $item['id'] ?>, 'approve')">
                        <i class="fas fa-check"></i> Onayla
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="contributionAction(<?= $item['id'] ?>, 'reject')">
                        <i class="fas fa-times"></i> Reddet
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<script>
function vote(itemId, voteValue, btn) {
    ajaxPost('ajax/vote.php', {item_id: itemId, vote: voteValue})
    .then(data => {
        if (data.success) {
            document.getElementById('like-' + itemId).querySelector('span').textContent = data.likes;
            document.getElementById('dislike-' + itemId).querySelector('span').textContent = data.dislikes;

            const likeBtn = document.getElementById('like-' + itemId);
            const dislikeBtn = document.getElementById('dislike-' + itemId);
            likeBtn.classList.remove('liked');
            dislikeBtn.classList.remove('disliked');

            if (data.user_vote === 1) likeBtn.classList.add('liked');
            else if (data.user_vote === -1) dislikeBtn.classList.add('disliked');
        }
    });
}

function contributionAction(itemId, action) {
    ajaxPost('ajax/contribution_action.php', {item_id: itemId, action: action})
    .then(data => {
        if (data.success) location.reload();
        else alert(data.error || 'Hata');
    });
}

function toggleFollow(collectionId) {
    ajaxPost('ajax/follow_collection.php', {collection_id: collectionId})
    .then(data => {
        if (data.success) location.reload();
    });
}

function toggleComments(itemId) {
    const section = document.getElementById('comments-' + itemId);
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
}

function addComment(itemId) {
    const input = document.getElementById('comment-input-' + itemId);
    const content = input.value.trim();
    if (!content) return;

    ajaxPost('ajax/add_comment.php', {item_id: itemId, content: content})
    .then(data => {
        if (data.success) location.reload();
        else alert(data.error || 'Hata');
    });
}

function deleteComment(commentId, btn) {
    if (!confirm('Yorumu silmek istediğinize emin misiniz?')) return;
    ajaxPost('ajax/delete_comment.php', {comment_id: commentId})
    .then(data => {
        if (data.success) btn.closest('.comment').remove();
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
