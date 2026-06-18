<?php
$pageTitle = 'Mesajlar';
require_once 'includes/header.php';

// Get conversation partners
$stmt = $pdo->prepare("
    SELECT u.*, 
        (SELECT content FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM users u
    WHERE u.id IN (
        SELECT DISTINCT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
        FROM messages WHERE sender_id = ? OR receiver_id = ?
    )
    ORDER BY last_message_time DESC
");
$stmt->execute([$currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id'], $currentUser['id']]);
$conversations = $stmt->fetchAll();

$activeUserId = (int)($_GET['user'] ?? 0);
$activeChat = null;
$chatMessages = [];

if ($activeUserId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$activeUserId]);
    $activeChat = $stmt->fetch();

    if ($activeChat) {
        // Mark as read
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->execute([$activeUserId, $currentUser['id']]);

        // Get messages
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
        $stmt->execute([$currentUser['id'], $activeUserId, $activeUserId, $currentUser['id']]);
        $chatMessages = $stmt->fetchAll();

        // Ensure user appears in conversation list
        $found = false;
        foreach ($conversations as $c) {
            if ($c['id'] == $activeUserId) { $found = true; break; }
        }
        if (!$found) {
            array_unshift($conversations, $activeChat);
        }
    }
}
?>

<div class="messages-layout">
    <!-- Conversations List -->
    <div class="conversations-list">
        <div class="conversations-list-header">
            <i class="fas fa-comments"></i> Sohbetler
        </div>
        <?php if (empty($conversations)): ?>
            <div class="empty-state" style="padding:30px;">
                <p style="font-size:0.85rem;">Henüz sohbet yok</p>
            </div>
        <?php else: ?>
            <?php foreach ($conversations as $conv): ?>
                <a href="<?= BASE_URL ?>/messages.php?user=<?= $conv['id'] ?>" class="conversation-item <?= $activeUserId == $conv['id'] ? 'active' : '' ?>" style="text-decoration:none;color:inherit;">
                    <img src="<?= avatar($conv['avatar']) ?>" alt="">
                    <div class="conversation-item-info">
                        <h4><?= e($conv['display_name'] ?: $conv['username']) ?>
                            <?php if (isset($conv['unread_count']) && $conv['unread_count'] > 0): ?>
                                <span class="badge-count" style="display:inline;margin-left:6px;"><?= $conv['unread_count'] ?></span>
                            <?php endif; ?>
                        </h4>
                        <p><?= e(isset($conv['last_message']) ? mb_substr($conv['last_message'], 0, 40) : '') ?></p>
                    </div>
                    <?php if (isset($conv['last_message_time'])): ?>
                        <span class="time"><?= timeAgo($conv['last_message_time']) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Chat Area -->
    <div class="chat-area">
        <?php if ($activeChat): ?>
            <div class="chat-header">
                <a href="<?= BASE_URL ?>/profile.php?id=<?= $activeChat['id'] ?>">
                    <img src="<?= avatar($activeChat['avatar']) ?>" alt="">
                </a>
                <h4><?= e($activeChat['display_name'] ?: $activeChat['username']) ?></h4>
            </div>
            <div class="chat-messages" id="chatMessages">
                <?php foreach ($chatMessages as $msg): ?>
                    <div class="message-bubble <?= $msg['sender_id'] == $currentUser['id'] ? 'sent' : 'received' ?>">
                        <?= e($msg['content']) ?>
                        <div class="time"><?= timeAgo($msg['created_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Mesajınızı yazın..." onkeypress="if(event.key==='Enter')sendMessage()">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        <?php else: ?>
            <div class="no-chat-selected">
                <div style="text-align:center;">
                    <i class="fas fa-comments" style="font-size:48px;opacity:0.3;margin-bottom:16px;display:block;"></i>
                    Sohbet başlatmak için bir kişi seçin
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const chatUserId = <?= $activeUserId ?: 0 ?>;

function sendMessage() {
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    if (!content || !chatUserId) return;

    ajaxPost('ajax/send_message.php', {receiver_id: chatUserId, content: content})
    .then(data => {
        if (data.success) {
            const container = document.getElementById('chatMessages');
            const bubble = document.createElement('div');
            bubble.className = 'message-bubble sent';
            bubble.innerHTML = escapeHtml(content) + '<div class="time">Az önce</div>';
            container.appendChild(bubble);
            container.scrollTop = container.scrollHeight;
            input.value = '';
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Scroll to bottom on load
if (document.getElementById('chatMessages')) {
    const c = document.getElementById('chatMessages');
    c.scrollTop = c.scrollHeight;
}

// Poll for new messages
if (chatUserId) {
    setInterval(() => {
        fetch(BASE_URL + '/ajax/get_messages.php?user_id=' + chatUserId + '&last_id=' + getLastMessageId())
        .then(r => r.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                const container = document.getElementById('chatMessages');
                data.messages.forEach(msg => {
                    const bubble = document.createElement('div');
                    bubble.className = 'message-bubble received';
                    bubble.innerHTML = escapeHtml(msg.content) + '<div class="time">Az önce</div>';
                    container.appendChild(bubble);
                });
                container.scrollTop = container.scrollHeight;
            }
        });
    }, 3000);
}

function getLastMessageId() {
    const bubbles = document.querySelectorAll('.message-bubble');
    return bubbles.length;
}
</script>

<?php require_once 'includes/footer.php'; ?>
