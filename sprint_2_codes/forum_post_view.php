<?php
require_once 'forum_common.php';

$postId = (int)($_GET['post_id'] ?? 0);
$viewerId = current_user_id();
$error = '';

$sql = "SELECT p.*, u.username, u.nickname
        FROM forum_posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.post_id = ? AND p.is_deleted = 0";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('SQL prepare failed: ' . $conn->error);
}
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post || !can_view_post($conn, $post, $viewerId)) {
    die('Post not found or not accessible.');
}

if ((int)$post['user_id'] === $viewerId) {
    $sql = "UPDATE forum_comments
            SET is_seen_by_post_author = 1
            WHERE post_id = ?
              AND user_id <> ?
              AND is_deleted = 0
              AND is_seen_by_post_author = 0";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $postId, $viewerId);
        $stmt->execute();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $parentCommentId = !empty($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
    $replyToUserId = !empty($_POST['reply_to_user_id']) ? (int)$_POST['reply_to_user_id'] : null;
    $recordedAudio = trim($_POST['recorded_audio_data'] ?? '');

    $hasUploads = isset($_FILES['comment_media_files'])
        && isset($_FILES['comment_media_files']['name'])
        && is_array($_FILES['comment_media_files']['name'])
        && !empty($_FILES['comment_media_files']['name'][0]);

    $hasRecordedAudio = ($recordedAudio !== '');

    if ($content === '' && !$hasUploads && !$hasRecordedAudio) {
        $error = 'Please enter text or upload at least one file or recording.';
    } else {
        $allMedia = [];

        if ($hasUploads) {
            $uploaded = upload_forum_media_files($_FILES['comment_media_files'], 'comments');
            if (!empty($uploaded)) {
                $allMedia = array_merge($allMedia, $uploaded);
            }
        }

        if ($hasRecordedAudio) {
            $audioMedia = save_base64_audio($recordedAudio, 'comments', 'comment_voice');
            if ($audioMedia) {
                $allMedia[] = $audioMedia;
            }
        }

        if ($content === '' && empty($allMedia)) {
            $error = 'The recorded voice could not be saved. Please record again or choose a file.';
        }
    }

    if ($error === '') {
        $seen = ((int)$post['user_id'] === $viewerId) ? 1 : 0;

        $sql = "INSERT INTO forum_comments (post_id, user_id, parent_comment_id, reply_to_user_id, content, is_seen_by_post_author)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = 'Comment insert prepare failed: ' . $conn->error;
        } else {
            $stmt->bind_param("iiiisi", $postId, $viewerId, $parentCommentId, $replyToUserId, $content, $seen);
            if ($stmt->execute()) {
                $commentId = $stmt->insert_id;
                $stmt->close();

                if (!empty($allMedia)) {
                    insert_media_records($conn, 'forum_comment_media', 'comment_id', $commentId, $allMedia);
                }

                header('Location: forum_post_view.php?post_id=' . $postId);
                exit;
            } else {
                cleanup_saved_media_files($allMedia);
                $error = 'Failed to save comment: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

$sql = "SELECT c.*,
               u.username, u.nickname,
               ru.username AS reply_to_username,
               ru.nickname AS reply_to_nickname
        FROM forum_comments c
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN users ru ON c.reply_to_user_id = ru.user_id
        WHERE c.post_id = ? AND c.is_deleted = 0
        ORDER BY c.created_at ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Comment query prepare failed: ' . $conn->error);
}
$stmt->bind_param("i", $postId);
$stmt->execute();
$commentsResult = $stmt->get_result();

$comments = [];
while ($row = $commentsResult->fetch_assoc()) {
    $comments[] = $row;
}
$stmt->close();

$postMedia = get_media_by_owner($conn, 'forum_post_media', 'post_id', $postId);

$pageTitle = $post['title'] ?: 'Post';
include 'forum_header.php';
?>

<div class="card">
    <div class="card-header">
        <div>
            <h1><?= h($post['title'] ?: 'Untitled Post') ?></h1>
            <div class="meta meta-line">
                <span>By</span>
                <a class="link-user" href="forum_profile.php?user_id=<?= (int)$post['user_id'] ?>">
                    <?= h($post['nickname'] ?: $post['username']) ?>
                </a>
                <span class="separator">&middot;</span>
                <span><?= h($post['visibility']) ?></span>
                <span class="separator">&middot;</span>
                <span><?= h($post['created_at']) ?></span>
            </div>
        </div>

        <?php if ((int)$post['user_id'] !== $viewerId): ?>
            <a class="btn btn-secondary" href="forum_profile.php?user_id=<?= (int)$post['user_id'] ?>">Visit Profile</a>
        <?php endif; ?>
    </div>

    <?php if ($post['content'] !== ''): ?>
        <div class="post-content"><?= h($post['content']) ?></div>
    <?php endif; ?>

    <?php if (!empty($postMedia)): ?>
        <div class="media-grid">
            <?php foreach ($postMedia as $media): ?>
                <div class="media-card">
                    <?php if ($media['file_type'] === 'image'): ?>
                        <img src="<?= h($media['file_path']) ?>" alt="post media">
                    <?php elseif ($media['file_type'] === 'video'): ?>
                        <video controls>
                            <source src="<?= h($media['file_path']) ?>">
                        </video>
                    <?php elseif ($media['file_type'] === 'audio'): ?>
                        <audio controls>
                            <source src="<?= h($media['file_path']) ?>">
                        </audio>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2>Leave a Comment</h2>
            <p class="section-intro">Reply with text, media, or a quick voice note.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="parent_comment_id" id="parent_comment_id">
        <input type="hidden" name="reply_to_user_id" id="reply_to_user_id">
        <input type="hidden" name="recorded_audio_data" id="recorded_audio_data">

        <div id="replying_to_box" class="alert alert-info" style="display:none;"></div>

        <textarea name="content" rows="4" placeholder="Write a comment..."></textarea>

        <label>Images / Videos / Audio Files</label>
        <input type="file" id="comment_media_files" name="comment_media_files[]" accept="image/*,video/*,audio/*" multiple style="display:none;">
        <div class="actions-row">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('comment_media_files').click();">Choose Files</button>
            <span class="meta" id="comment_media_files_text">No files selected.</span>
        </div>
        <div class="meta">You can upload multiple images, videos, or audio files.</div>

        <div id="recordedAudioPreviewWrap" class="media-card" style="display:none; margin:14px 0;">
            <div class="meta" style="margin-bottom:8px;">Recorded voice preview</div>
            <audio id="recordedAudioPreview" controls preload="metadata" style="width:100%;"></audio>
        </div>
        <div class="actions-row" style="margin:14px 0;">
            <button type="button" class="btn btn-dark" id="recordBtn">Hold to Record Voice</button>
            <button type="button" class="btn btn-secondary" id="clearRecordBtn">Clear Voice</button>
            <span class="meta" id="recordStatus">No recorded voice.</span>
        </div>

        <button class="btn btn-primary">Comment</button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2>Comments</h2>
            <p class="section-intro">Follow the discussion thread below.</p>
        </div>
    </div>

    <?php if (empty($comments)): ?>
        <div class="empty-state">No comments yet. Be the first to respond.</div>
    <?php endif; ?>

    <?php foreach ($comments as $comment): ?>
        <?php $commentMedia = get_media_by_owner($conn, 'forum_comment_media', 'comment_id', (int)$comment['comment_id']); ?>
        <div class="comment">
            <div class="row-between">
                <div>
                    <a class="link-user" href="forum_profile.php?user_id=<?= (int)$comment['user_id'] ?>">
                        <?= h($comment['nickname'] ?: $comment['username']) ?>
                    </a>

                    <?php if ($comment['reply_to_user_id']): ?>
                        <span class="reply-tag">@<?= h($comment['reply_to_nickname'] ?: $comment['reply_to_username']) ?></span>
                    <?php endif; ?>

                    <div class="meta"><?= h($comment['created_at']) ?></div>
                </div>

                <div class="actions-row">
                    <button
                        class="btn btn-secondary reply-btn"
                        data-comment-id="<?= (int)$comment['comment_id'] ?>"
                        data-user-id="<?= (int)$comment['user_id'] ?>"
                        data-user-name="<?= h($comment['nickname'] ?: $comment['username']) ?>">
                        Reply
                    </button>

                    <?php if ((int)$comment['user_id'] === $viewerId): ?>
                        <a class="btn btn-danger" href="forum_delete_comment.php?comment_id=<?= (int)$comment['comment_id'] ?>&post_id=<?= (int)$postId ?>">
                            Delete
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($comment['content'] !== ''): ?>
                <div class="post-content" style="margin-top:10px;"><?= h($comment['content']) ?></div>
            <?php endif; ?>

            <?php if (!empty($commentMedia)): ?>
                <div class="media-grid">
                    <?php foreach ($commentMedia as $media): ?>
                        <div class="media-card">
                            <?php if ($media['file_type'] === 'image'): ?>
                                <img src="<?= h($media['file_path']) ?>" alt="comment media">
                            <?php elseif ($media['file_type'] === 'video'): ?>
                                <video controls>
                                    <source src="<?= h($media['file_path']) ?>">
                                </video>
                            <?php elseif ($media['file_type'] === 'audio'): ?>
                                <audio controls>
                                    <source src="<?= h($media['file_path']) ?>">
                                </audio>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
const commentFileInput = document.getElementById('comment_media_files');
const commentFileText = document.getElementById('comment_media_files_text');
if (commentFileInput && commentFileText) {
    commentFileInput.addEventListener('change', function() {
        const count = this.files.length;
        commentFileText.textContent = count > 0 ? `${count} file(s) selected` : 'No files selected.';
    });
}

let mediaRecorder = null;
let audioChunks = [];
let streamRef = null;
let isRecordingStarting = false;
let isRecordingProcessing = false;
const recordBtn = document.getElementById('recordBtn');
const clearRecordBtn = document.getElementById('clearRecordBtn');
const recordStatus = document.getElementById('recordStatus');
const recordedAudioInput = document.getElementById('recorded_audio_data');
const recordedAudioPreviewWrap = document.getElementById('recordedAudioPreviewWrap');
const recordedAudioPreview = document.getElementById('recordedAudioPreview');
const commentForm = document.querySelector('form[method="post"][enctype="multipart/form-data"]');

function setRecordingButtonsDisabled(disabled) {
    recordBtn.disabled = disabled;
    clearRecordBtn.disabled = disabled;
}

function setRecordedAudioPreview(dataUrl) {
    if (!recordedAudioPreviewWrap || !recordedAudioPreview) {
        return;
    }

    if (dataUrl) {
        recordedAudioPreview.src = dataUrl;
        recordedAudioPreviewWrap.style.display = 'block';
    } else {
        recordedAudioPreview.pause();
        recordedAudioPreview.removeAttribute('src');
        recordedAudioPreview.load();
        recordedAudioPreviewWrap.style.display = 'none';
    }
}

async function startRecording() {
    if (isRecordingStarting || isRecordingProcessing || (mediaRecorder && mediaRecorder.state === 'recording')) {
        return;
    }

    try {
        isRecordingStarting = true;
        setRecordingButtonsDisabled(true);
        streamRef = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(streamRef);
        audioChunks = [];

        mediaRecorder.ondataavailable = event => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        mediaRecorder.onstop = () => {
            isRecordingProcessing = true;
            setRecordingButtonsDisabled(true);
            const audioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
            const reader = new FileReader();
            reader.onloadend = () => {
                recordedAudioInput.value = reader.result;
                setRecordedAudioPreview(reader.result);
                recordStatus.textContent = 'Voice recorded successfully.';
                isRecordingProcessing = false;
                setRecordingButtonsDisabled(false);
            };
            reader.readAsDataURL(audioBlob);

            if (streamRef) {
                streamRef.getTracks().forEach(track => track.stop());
                streamRef = null;
            }
        };

        mediaRecorder.start();
        recordStatus.textContent = 'Recording... release to stop';
    } catch (err) {
        recordStatus.textContent = 'Microphone permission denied or unavailable.';
        setRecordingButtonsDisabled(false);
    } finally {
        isRecordingStarting = false;
    }
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
    }
}

if (recordBtn) {
    recordBtn.addEventListener('mousedown', startRecording);
    recordBtn.addEventListener('mouseup', stopRecording);
    recordBtn.addEventListener('mouseleave', stopRecording);
    recordBtn.addEventListener('touchstart', function(e) {
        e.preventDefault();
        startRecording();
    });
    recordBtn.addEventListener('touchend', function(e) {
        e.preventDefault();
        stopRecording();
    });
}

if (clearRecordBtn) {
    clearRecordBtn.addEventListener('click', function() {
        if (isRecordingProcessing) {
            return;
        }
        recordedAudioInput.value = '';
        setRecordedAudioPreview('');
        recordStatus.textContent = 'No recorded voice.';
    });
}

if (commentForm) {
    commentForm.addEventListener('submit', function(e) {
        if (isRecordingStarting || isRecordingProcessing || (mediaRecorder && mediaRecorder.state === 'recording')) {
            e.preventDefault();
            recordStatus.textContent = 'Please wait for the voice recording to finish processing.';
        }
    });
}
</script>

<?php include 'forum_footer.php'; ?>
