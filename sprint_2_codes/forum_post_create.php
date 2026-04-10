<?php
require_once 'forum_common.php';
$pageTitle = 'Create Post';
$userId = current_user_id();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $visibility = $_POST['visibility'] ?? 'public';
    $recordedAudio = trim($_POST['recorded_audio_data'] ?? '');

    $allowed = ['public', 'followers_only', 'friends_only', 'private'];
    $hasUploads = !empty($_FILES['media_files']['name'][0]);
    $hasRecordedAudio = ($recordedAudio !== '');

    if ($content === '' && !$hasUploads && !$hasRecordedAudio) {
        $error = 'Please enter text or upload at least one file or recording.';
    } elseif (!in_array($visibility, $allowed, true)) {
        $error = 'Invalid visibility.';
    } else {
        $allMedia = [];

        if ($hasUploads) {
            $allMedia = array_merge($allMedia, upload_forum_media_files($_FILES['media_files'], 'posts'));
        }

        if ($hasRecordedAudio) {
            $audioMedia = save_base64_audio($recordedAudio, 'posts', 'post_voice');
            if ($audioMedia) {
                $allMedia[] = $audioMedia;
            }
        }

        if ($content === '' && empty($allMedia)) {
            $error = 'The recorded voice could not be saved. Please record again or choose a file.';
        }
    }

    if ($error === '') {
        $sql = "INSERT INTO forum_posts (user_id, title, content, visibility)
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $userId, $title, $content, $visibility);

        if ($stmt->execute()) {
            $postId = $stmt->insert_id;
            $stmt->close();

            if (!empty($allMedia)) {
                insert_media_records($conn, 'forum_post_media', 'post_id', $postId, $allMedia);
            }

            header('Location: forum.php?posted=1');
            exit;
        } else {
            cleanup_saved_media_files($allMedia);
            $error = 'Failed to publish the post: ' . $conn->error;
            $stmt->close();
        }
    }
}

include 'forum_header.php';
?>

<div class="card">
    <div class="card-header">
        <div>
            <h2>Create a New Post</h2>
            <p class="section-intro">Share a thought, upload media, and choose exactly who should be able to view it.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="forum_post_create.php" enctype="multipart/form-data">
        <label>Title</label>
        <input type="text" name="title" maxlength="255" placeholder="Enter a title">

        <label>Content</label>
        <textarea name="content" rows="8" placeholder="Share your thoughts..."></textarea>

        <label>Visibility</label>
        <select name="visibility">
            <option value="public">Public</option>
            <option value="followers_only">Followers only</option>
            <option value="friends_only">Friends only</option>
            <option value="private">Private</option>
        </select>

        <label>Images / Videos / Audio Files</label>
        <input type="file" id="media_files" name="media_files[]" accept="image/*,video/*,audio/*" multiple style="display:none;">
        <div class="actions-row">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('media_files').click();">Choose Files</button>
            <span class="meta" id="media_files_text">No files selected.</span>
        </div>
        <div class="meta">You can upload multiple images, videos, or audio files.</div>

        <input type="hidden" name="recorded_audio_data" id="recorded_audio_data">
        <div id="recordedAudioPreviewWrap" class="media-card" style="display:none; margin:14px 0;">
            <div class="meta" style="margin-bottom:8px;">Recorded voice preview</div>
            <audio id="recordedAudioPreview" controls preload="metadata" style="width:100%;"></audio>
        </div>
        <div class="actions-row" style="margin:14px 0;">
            <button type="button" class="btn btn-dark" id="recordBtn">Hold to Record Voice</button>
            <button type="button" class="btn btn-secondary" id="clearRecordBtn">Clear Voice</button>
            <span class="meta" id="recordStatus">No recorded voice.</span>
        </div>

        <button class="btn btn-primary" type="submit">Publish</button>
    </form>
</div>

<script>
document.getElementById('media_files').addEventListener('change', function() {
    const count = this.files.length;
    document.getElementById('media_files_text').textContent =
        count > 0 ? `${count} file(s) selected` : 'No files selected.';
});

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
const postForm = document.querySelector('form[action="forum_post_create.php"]');

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

clearRecordBtn.addEventListener('click', function() {
    if (isRecordingProcessing) {
        return;
    }
    recordedAudioInput.value = '';
    setRecordedAudioPreview('');
    recordStatus.textContent = 'No recorded voice.';
});

if (postForm) {
    postForm.addEventListener('submit', function(e) {
        if (isRecordingStarting || isRecordingProcessing || (mediaRecorder && mediaRecorder.state === 'recording')) {
            e.preventDefault();
            recordStatus.textContent = 'Please wait for the voice recording to finish processing.';
        }
    });
}
</script>

<?php include 'forum_footer.php'; ?>
