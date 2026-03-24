<?php
require_once 'forum_common.php';
$other_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$other_user = $other_user_id ? find_user($conn, $other_user_id) : null;
$error = $_GET['error'] ?? '';
$page_title = 'Inbox';

$messages = [];
if ($other_user) {
    $stmt = $conn->prepare("SELECT pm.*, su.username AS sender_username, su.nickname AS sender_nickname
                            FROM private_messages pm
                            JOIN users su ON pm.sender_id = su.user_id
                            WHERE ((pm.sender_id = ? AND pm.receiver_id = ? AND pm.is_deleted_by_sender = 0)
                                OR (pm.sender_id = ? AND pm.receiver_id = ? AND pm.is_deleted_by_receiver = 0))
                            ORDER BY pm.created_at ASC");
    $stmt->bind_param("iiii", $current_user_id, $other_user_id, $other_user_id, $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $messages[] = $row;
}

include 'forum_header.php';
?>
<div class="grid-two">
    <div class="card">
        <h2>Private Messages</h2>
        <?php if ($error === 'limit'): ?>
            <div class="alert alert-warning">You can only exchange one message before becoming friends.</div>
        <?php endif; ?>
        <?php if ($other_user): ?>
            <div class="meta" style="margin-bottom:14px;">Chat with <?= h($other_user['nickname'] ?: $other_user['username']) ?></div>
            <?php foreach ($messages as $message): ?>
                <div class="message-bubble <?= ((int)$message['sender_id'] === $current_user_id) ? 'message-own' : 'message-other' ?>">
                    <strong><?= h($message['sender_nickname'] ?: $message['sender_username']) ?></strong>
                    <div style="margin-top:6px;"><?= h($message['content']) ?></div>
                    <div class="meta" style="margin-top:6px;"><?= h($message['created_at']) ?></div>
                </div>
            <?php endforeach; ?>
            <form method="post" action="forum_message_send.php">
                <input type="hidden" name="receiver_id" value="<?= (int)$other_user['user_id'] ?>">
                <textarea name="content" rows="4" required></textarea>
                <button class="btn btn-dark" type="submit">Send</button>
            </form>
        <?php else: ?>
            <div class="meta">Open someone’s forum profile and click Message to start chatting.</div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Quick Note</h3>
        <div class="post-content">Before two users become friends, they can only exchange one message in total. Once they follow each other and become friends, normal chatting is allowed.</div>
    </div>
</div>
<?php include 'forum_footer.php'; ?>
