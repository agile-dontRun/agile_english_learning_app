<?php
require_once 'forum_common.php';

ensure_private_call_tables($conn);

$currentId = current_user_id();
$otherId = (int)($_GET['user_id'] ?? 0);
$pageTitle = 'Messages';
$error = $_GET['error'] ?? '';
$sent = $_GET['sent'] ?? '';

$contacts = [];

$sql = "
    SELECT DISTINCT u.user_id, u.username, u.nickname
    FROM users u
    WHERE u.user_id IN (
        SELECT CASE
            WHEN uf.user_id_1 = ? THEN uf.user_id_2
            ELSE uf.user_id_1
        END
        FROM user_friendships uf
        WHERE uf.user_id_1 = ? OR uf.user_id_2 = ?

        UNION

        SELECT pm.sender_id
        FROM private_messages pm
        WHERE pm.receiver_id = ?

        UNION

        SELECT pm.receiver_id
        FROM private_messages pm
        WHERE pm.sender_id = ?
    )
    ORDER BY u.nickname, u.username
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $currentId, $currentId, $currentId, $currentId, $currentId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $contacts[] = $row;
}
$stmt->close();

$otherUser = null;
$messages = [];

if ($otherId > 0) {
    $otherUser = find_user($conn, $otherId);

    if ($otherUser) {
        $sql = "UPDATE private_messages
                SET is_read = 1
                WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $otherId, $currentId);
        $stmt->execute();
        $stmt->close();

        $sql = "
            SELECT pm.*, su.username AS sender_username, su.nickname AS sender_nickname
            FROM private_messages pm
            JOIN users su ON pm.sender_id = su.user_id
            WHERE (
                (pm.sender_id = ? AND pm.receiver_id = ? AND pm.is_deleted_by_sender = 0)
                OR
                (pm.sender_id = ? AND pm.receiver_id = ? AND pm.is_deleted_by_receiver = 0)
            )
            ORDER BY pm.created_at ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $currentId, $otherId, $otherId, $currentId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
    }
}

$otherDisplayName = $otherUser ? ($otherUser['nickname'] ?: $otherUser['username']) : '';

include 'forum_header.php';
?>

<style>
    .chat-header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .call-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .call-status {
        margin: 0 16px;
        padding: 12px 14px;
        border-radius: 14px;
        background: #eef3f8;
        color: var(--oxford-blue);
        display: none;
    }

    .call-status.show {
        display: block;
    }

    .call-panel {
        position: fixed;
        right: 24px;
        bottom: 24px;
        width: min(420px, calc(100vw - 24px));
        z-index: 1200;
        background: rgba(0, 21, 45, 0.96);
        color: #fff;
        border-radius: 24px;
        box-shadow: 0 22px 50px rgba(0, 0, 0, 0.28);
        overflow: hidden;
        display: none;
    }

    .call-panel.show {
        display: block;
    }

    .call-panel-header {
        padding: 18px 20px 8px;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .call-panel-meta {
        padding: 0 20px 14px;
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.92rem;
    }

    .call-videos {
        position: relative;
        background: #000;
        min-height: 260px;
    }

    .call-videos video {
        display: block;
        width: 100%;
        background: #000;
    }

    .call-videos.video-mode #remoteVideo {
        min-height: 260px;
        object-fit: cover;
    }

    .call-videos.audio-mode #remoteVideo {
        display: none;
    }

    .call-audio-placeholder {
        min-height: 260px;
        display: none;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 24px;
        background: radial-gradient(circle at top, rgba(196, 166, 97, 0.3), transparent 55%), #08192f;
    }

    .call-videos.audio-mode .call-audio-placeholder {
        display: flex;
    }

    .call-local-video {
        position: absolute;
        right: 14px;
        bottom: 14px;
        width: 112px;
        border-radius: 16px;
        border: 2px solid rgba(255, 255, 255, 0.55);
        overflow: hidden;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
        background: #111;
    }

    .call-videos.audio-mode .call-local-video {
        display: none;
    }

    .call-controls {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        padding: 16px 20px 20px;
    }
</style>

<div class="split-layout">
    <div class="chat-sidebar">
        <div class="chat-sidebar-title">Chats</div>

        <?php if (empty($contacts)): ?>
            <div class="empty-state" style="padding:18px 20px 20px;">No conversations yet.</div>
        <?php endif; ?>

        <?php foreach ($contacts as $contact): ?>
            <?php
            $contactUnread = 0;
            $sql = "SELECT COUNT(*) AS total
                    FROM private_messages
                    WHERE sender_id = ? AND receiver_id = ? AND is_read = 0 AND is_deleted_by_receiver = 0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $contact['user_id'], $currentId);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $contactUnread = (int)($r['total'] ?? 0);
            $stmt->close();

            $alreadyFollowing = is_following($conn, $currentId, (int)$contact['user_id']);
            ?>
            <div class="chat-contact <?= $otherId === (int)$contact['user_id'] ? 'active' : '' ?>">
                <a href="forum_inbox.php?user_id=<?= (int)$contact['user_id'] ?>" style="text-decoration:none; color:inherit; flex:1;">
                    <div style="font-weight:700; color:#002147;">
                        <?= h($contact['nickname'] ?: $contact['username']) ?>
                        <?php if ($contactUnread > 0): ?>
                            <span class="badge"><?= $contactUnread ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="meta">@<?= h($contact['username']) ?></div>
                </a>

                <?php if ((int)$contact['user_id'] !== $currentId): ?>
                    <?php if ($alreadyFollowing): ?>
                        <a class="btn btn-secondary" style="padding:8px 12px;" href="forum_follow_action.php?user_id=<?= (int)$contact['user_id'] ?>&action=unfollow&redirect=inbox">Unfollow</a>
                    <?php else: ?>
                        <a class="btn btn-primary" style="padding:8px 12px;" href="forum_follow_action.php?user_id=<?= (int)$contact['user_id'] ?>&action=follow&redirect=inbox">Follow</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="chat-window">
        <?php if ($otherUser): ?>
            <div class="chat-header">
                <div class="chat-header-bar">
                    <span><?= h($otherDisplayName) ?></span>
                    <div class="call-actions">
                        <button type="button" class="btn btn-secondary" id="startAudioCallBtn">Voice Call</button>
                        <button type="button" class="btn btn-primary" id="startVideoCallBtn">Video Call</button>
                    </div>
                </div>
            </div>

            <?php if ($error === 'limit'): ?>
                <div class="alert alert-warning" style="margin:16px 16px 0;">You can only exchange one message before becoming friends.</div>
            <?php elseif ($error === 'empty'): ?>
                <div class="alert alert-warning" style="margin:16px 16px 0;">Please enter text or choose at least one file or recording.</div>
            <?php elseif ($error === 'send_failed'): ?>
                <div class="alert alert-danger" style="margin:16px 16px 0;">Message sending failed. Please try again.</div>
            <?php elseif ($error === 'record_failed'): ?>
                <div class="alert alert-danger" style="margin:16px 16px 0;">The recorded voice could not be saved. Please record again or choose a file.</div>
            <?php elseif ($error === 'sql_prepare'): ?>
                <div class="alert alert-danger" style="margin:16px 16px 0;">Database prepare failed. Please check table structure.</div>
            <?php elseif ($error === 'invalid_receiver'): ?>
                <div class="alert alert-danger" style="margin:16px 16px 0;">Invalid receiver.</div>
            <?php elseif ($sent === '1'): ?>
                <div class="alert alert-info" style="margin:16px 16px 0;">Message sent successfully.</div>
            <?php endif; ?>

            <div class="call-status" id="callStatusBar"></div>

            <div class="chat-messages" id="chatMessages">
                <?php foreach ($messages as $message): ?>
                    <?php $messageMedia = get_media_by_owner($conn, 'private_message_media', 'message_id', (int)$message['message_id']); ?>
                    <div class="msg-row <?= ((int)$message['sender_id'] === $currentId) ? 'me' : '' ?>">
                        <div class="msg-bubble">
                            <?php if ($message['content'] !== ''): ?>
                                <div><?= h($message['content']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($messageMedia)): ?>
                                <div class="media-grid">
                                    <?php foreach ($messageMedia as $media): ?>
                                        <div class="media-card">
                                            <?php if ($media['file_type'] === 'image'): ?>
                                                <img src="<?= h($media['file_path']) ?>" alt="message media">
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

                            <div class="msg-time"><?= h($message['created_at']) ?></div>

                            <?php if ((int)$message['sender_id'] === $currentId): ?>
                                <div style="margin-top:10px;">
                                    <a class="btn btn-danger" style="padding:8px 12px;" href="forum_delete_message.php?message_id=<?= (int)$message['message_id'] ?>&user_id=<?= (int)$otherUser['user_id'] ?>">Delete</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="chat-input">
                <form method="post" action="forum_message_send.php" enctype="multipart/form-data">
                    <input type="hidden" name="receiver_id" value="<?= (int)$otherUser['user_id'] ?>">
                    <input type="hidden" name="recorded_audio_data" id="recorded_audio_data">

                    <textarea name="content" rows="3" placeholder="Type a message..."></textarea>

                    <label>Images / Videos / Audio Files</label>
                    <input type="file" id="message_media_files" name="message_media_files[]" accept="image/*,video/*,audio/*" multiple style="display:none;">
                    <div class="actions-row">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('message_media_files').click();">Choose Files</button>
                        <span class="meta" id="message_media_files_text">No files selected.</span>
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

                    <button class="btn btn-primary" type="submit">Send</button>
                </form>
            </div>

            <script>
                const chatBox = document.getElementById('chatMessages');
                if (chatBox) {
                    chatBox.scrollTop = chatBox.scrollHeight;
                }

                const fileInput = document.getElementById('message_media_files');
                const fileText = document.getElementById('message_media_files_text');
                if (fileInput && fileText) {
                    fileInput.addEventListener('change', function() {
                        const count = this.files.length;
                        fileText.textContent = count > 0 ? `${count} file(s) selected` : 'No files selected.';
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
                const messageForm = document.querySelector('.chat-input form');

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

                if (messageForm) {
                    messageForm.addEventListener('submit', function(e) {
                        if (isRecordingStarting || isRecordingProcessing || (mediaRecorder && mediaRecorder.state === 'recording')) {
                            e.preventDefault();
                            recordStatus.textContent = 'Please wait for the voice recording to finish processing.';
                        }
                    });
                }

                const CALL_API_URL = 'forum_call_api.php';
                const currentUserId = <?= (int)$currentId ?>;
                const otherUserId = <?= (int)$otherUser['user_id'] ?>;
                const otherUserName = <?= json_encode($otherDisplayName, JSON_UNESCAPED_UNICODE) ?>;
                const rtcConfig = {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' },
                        { urls: 'stun:stun1.l.google.com:19302' }
                    ]
                };

                let currentCallId = null;
                let currentCallType = null;
                let currentRole = null;
                let peerConnection = null;
                let localStream = null;
                let remoteStream = null;
                let incomingOffer = null;
                let pollTimer = null;
                let pendingCandidates = [];
                let queuedRemoteCandidates = [];

                const callStatusBar = document.getElementById('callStatusBar');
                const startAudioCallBtn = document.getElementById('startAudioCallBtn');
                const startVideoCallBtn = document.getElementById('startVideoCallBtn');

                const callPanel = document.createElement('div');
                callPanel.className = 'call-panel';
                callPanel.id = 'callPanel';
                callPanel.innerHTML = `
                    <div class="call-panel-header" id="callPanelTitle">Call</div>
                    <div class="call-panel-meta" id="callPanelMeta">Preparing connection...</div>
                    <div class="call-videos audio-mode" id="callVideos">
                        <video id="remoteVideo" autoplay playsinline></video>
                        <div class="call-audio-placeholder" id="callAudioPlaceholder">Voice call in progress</div>
                        <div class="call-local-video">
                            <video id="localVideo" autoplay playsinline muted></video>
                        </div>
                    </div>
                    <div class="call-controls">
                        <button type="button" class="btn btn-primary" id="acceptCallBtn" style="display:none;">Answer</button>
                        <button type="button" class="btn btn-secondary" id="declineCallBtn" style="display:none;">Decline</button>
                        <button type="button" class="btn btn-danger" id="endCallBtn" style="display:none;">Hang Up</button>
                    </div>
                `;
                document.body.appendChild(callPanel);

                const callPanelTitle = document.getElementById('callPanelTitle');
                const callPanelMeta = document.getElementById('callPanelMeta');
                const callVideos = document.getElementById('callVideos');
                const remoteVideo = document.getElementById('remoteVideo');
                const localVideo = document.getElementById('localVideo');
                const acceptCallBtn = document.getElementById('acceptCallBtn');
                const declineCallBtn = document.getElementById('declineCallBtn');
                const endCallBtn = document.getElementById('endCallBtn');

                function showCallStatus(message, keepVisible = false) {
                    callStatusBar.textContent = message;
                    callStatusBar.classList.add('show');

                    if (!keepVisible) {
                        window.clearTimeout(showCallStatus._timer);
                        showCallStatus._timer = window.setTimeout(() => {
                            if (!currentCallId && !incomingOffer) {
                                callStatusBar.classList.remove('show');
                            }
                        }, 4000);
                    }
                }

                function updateCallPanel(mode, title, meta) {
                    callPanel.classList.add('show');
                    callPanelTitle.textContent = title;
                    callPanelMeta.textContent = meta;
                    callVideos.classList.toggle('video-mode', mode === 'video');
                    callVideos.classList.toggle('audio-mode', mode !== 'video');
                }

                function resetCallUi() {
                    incomingOffer = null;
                    currentCallId = null;
                    currentCallType = null;
                    currentRole = null;
                    callPanel.classList.remove('show');
                    acceptCallBtn.style.display = 'none';
                    declineCallBtn.style.display = 'none';
                    endCallBtn.style.display = 'none';
                    callStatusBar.classList.remove('show');
                }

                async function apiRequest(payload) {
                    const response = await fetch(CALL_API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    return response.json();
                }

                async function ensureLocalStream(callType) {
                    const needsVideo = callType === 'video';
                    const hasVideoTrack = localStream && localStream.getVideoTracks().length > 0;

                    if (localStream && hasVideoTrack === needsVideo) {
                        return localStream;
                    }

                    stopLocalStream();

                    localStream = await navigator.mediaDevices.getUserMedia({
                        audio: true,
                        video: needsVideo
                    });
                    localVideo.srcObject = localStream;
                    return localStream;
                }

                function closePeerConnection() {
                    if (peerConnection) {
                        peerConnection.onicecandidate = null;
                        peerConnection.ontrack = null;
                        peerConnection.close();
                        peerConnection = null;
                    }
                }

                function stopLocalStream() {
                    if (localStream) {
                        localStream.getTracks().forEach(track => track.stop());
                        localStream = null;
                    }
                    if (localVideo) {
                        localVideo.srcObject = null;
                    }
                }

                function clearRemoteStream() {
                    if (remoteStream) {
                        remoteStream.getTracks().forEach(track => track.stop());
                    }
                    remoteStream = null;
                    remoteVideo.srcObject = null;
                }

                async function setupPeerConnection(callType) {
                    closePeerConnection();
                    clearRemoteStream();
                    pendingCandidates = [];
                    queuedRemoteCandidates = [];

                    peerConnection = new RTCPeerConnection(rtcConfig);
                    remoteStream = new MediaStream();
                    remoteVideo.srcObject = remoteStream;

                    peerConnection.ontrack = event => {
                        event.streams[0].getTracks().forEach(track => remoteStream.addTrack(track));
                    };

                    peerConnection.onicecandidate = async event => {
                        if (!event.candidate) {
                            return;
                        }

                        if (!currentCallId) {
                            pendingCandidates.push(event.candidate.toJSON());
                            return;
                        }

                        try {
                            await apiRequest({
                                action: 'send_candidate',
                                call_id: currentCallId,
                                candidate: event.candidate.toJSON()
                            });
                        } catch (error) {
                            console.error(error);
                        }
                    };

                    const stream = await ensureLocalStream(callType);
                    stream.getTracks().forEach(track => peerConnection.addTrack(track, stream));
                }

                async function flushQueuedRemoteCandidates() {
                    if (!peerConnection || !peerConnection.remoteDescription) {
                        return;
                    }

                    const candidates = queuedRemoteCandidates;
                    queuedRemoteCandidates = [];

                    for (const candidate of candidates) {
                        try {
                            await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                        } catch (error) {
                            console.error(error);
                        }
                    }
                }

                async function startCall(callType) {
                    if (!window.RTCPeerConnection || !navigator.mediaDevices) {
                        showCallStatus('Your browser does not support web audio/video calling.', true);
                        return;
                    }

                    try {
                        currentCallType = callType;
                        currentRole = 'caller';
                        updateCallPanel(callType, `${callType === 'video' ? 'Video' : 'Voice'} call with ${otherUserName}`, 'Calling...');
                        endCallBtn.style.display = 'inline-flex';
                        await setupPeerConnection(callType);

                        const offer = await peerConnection.createOffer();
                        await peerConnection.setLocalDescription(offer);

                        const result = await apiRequest({
                            action: 'start_call',
                            receiver_id: otherUserId,
                            call_type: callType,
                            offer
                        });

                        if (!result.ok) {
                            throw new Error(result.error || 'Failed to start call');
                        }

                        currentCallId = result.call_id;
                        for (const candidate of pendingCandidates) {
                            await apiRequest({
                                action: 'send_candidate',
                                call_id: currentCallId,
                                candidate
                            });
                        }
                        pendingCandidates = [];
                        showCallStatus(`${callType === 'video' ? 'Video' : 'Voice'} call request sent to ${otherUserName}.`, true);
                        callPanelMeta.textContent = 'Waiting for the other side to answer...';
                    } catch (error) {
                        console.error(error);
                        showCallStatus('Unable to start the call. Please allow camera/microphone access and try again.', true);
                        await finishCallLocally();
                    }
                }

                async function receiveIncomingOffer(signal) {
                    if (currentCallId && currentCallId !== signal.call_id) {
                        return;
                    }

                    incomingOffer = signal.payload;
                    currentCallId = signal.call_id;
                    currentCallType = signal.call_type === 'video' ? 'video' : 'audio';
                    currentRole = 'callee';

                    updateCallPanel(currentCallType, `${currentCallType === 'video' ? 'Video' : 'Voice'} call from ${otherUserName}`, 'Incoming call');
                    acceptCallBtn.style.display = 'inline-flex';
                    declineCallBtn.style.display = 'inline-flex';
                    endCallBtn.style.display = 'none';
                    showCallStatus(`${otherUserName} is calling you.`, true);
                }

                async function acceptIncomingCall() {
                    if (!incomingOffer || !currentCallId) {
                        return;
                    }

                    try {
                        updateCallPanel(currentCallType, `${currentCallType === 'video' ? 'Video' : 'Voice'} call with ${otherUserName}`, 'Connecting...');
                        acceptCallBtn.style.display = 'none';
                        declineCallBtn.style.display = 'none';
                        endCallBtn.style.display = 'inline-flex';

                        await setupPeerConnection(currentCallType);
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(incomingOffer));
                        await flushQueuedRemoteCandidates();

                        const answer = await peerConnection.createAnswer();
                        await peerConnection.setLocalDescription(answer);

                        const result = await apiRequest({
                            action: 'answer_call',
                            call_id: currentCallId,
                            answer
                        });

                        if (!result.ok) {
                            throw new Error(result.error || 'Failed to answer call');
                        }

                        showCallStatus(`Connected with ${otherUserName}.`, true);
                        callPanelMeta.textContent = 'Connected';
                        incomingOffer = null;
                    } catch (error) {
                        console.error(error);
                        showCallStatus('Unable to answer the call. Please try again.', true);
                        await endCurrentCall('failed');
                    }
                }

                async function handleSignal(signal) {
                    if (signal.signal_type === 'offer') {
                        await receiveIncomingOffer(signal);
                        return;
                    }

                    if (!currentCallId || signal.call_id !== currentCallId) {
                        return;
                    }

                    if (signal.signal_type === 'answer' && peerConnection && signal.payload) {
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(signal.payload));
                        await flushQueuedRemoteCandidates();
                        callPanelMeta.textContent = 'Connected';
                        showCallStatus(`Connected with ${otherUserName}.`, true);
                        return;
                    }

                    if (signal.signal_type === 'candidate' && signal.payload) {
                        if (!peerConnection || !peerConnection.remoteDescription) {
                            queuedRemoteCandidates.push(signal.payload);
                            return;
                        }

                        try {
                            await peerConnection.addIceCandidate(new RTCIceCandidate(signal.payload));
                        } catch (error) {
                            console.error(error);
                        }
                        return;
                    }

                    if (signal.signal_type === 'hangup') {
                        const reason = signal.payload && signal.payload.reason ? signal.payload.reason : 'ended';
                        const labelMap = {
                            declined: `${otherUserName} declined the call.`,
                            missed: `You missed the call from ${otherUserName}.`,
                            failed: 'The call ended because the connection failed.',
                            ended: 'The call has ended.'
                        };
                        showCallStatus(labelMap[reason] || 'The call has ended.', true);
                        await finishCallLocally();
                    }
                }

                async function pollSignals() {
                    try {
                        const response = await fetch(`${CALL_API_URL}?action=poll&other_id=${otherUserId}`, {
                            cache: 'no-store'
                        });
                        const data = await response.json();
                        if (!data.ok || !Array.isArray(data.signals)) {
                            return;
                        }

                        for (const signal of data.signals) {
                            await handleSignal(signal);
                        }
                    } catch (error) {
                        console.error(error);
                    }
                }

                async function finishCallLocally() {
                    closePeerConnection();
                    stopLocalStream();
                    clearRemoteStream();
                    resetCallUi();
                }

                async function endCurrentCall(reason = 'ended') {
                    const callId = currentCallId;
                    try {
                        if (callId) {
                            await apiRequest({
                                action: 'end_call',
                                call_id: callId,
                                reason
                            });
                        }
                    } catch (error) {
                        console.error(error);
                    } finally {
                        await finishCallLocally();
                    }
                }

                startAudioCallBtn.addEventListener('click', () => startCall('audio'));
                startVideoCallBtn.addEventListener('click', () => startCall('video'));
                acceptCallBtn.addEventListener('click', acceptIncomingCall);
                declineCallBtn.addEventListener('click', () => endCurrentCall('declined'));
                endCallBtn.addEventListener('click', () => endCurrentCall('ended'));

                pollSignals();
                pollTimer = window.setInterval(pollSignals, 2500);

                window.addEventListener('beforeunload', () => {
                    if (pollTimer) {
                        window.clearInterval(pollTimer);
                    }
                    if (currentCallId) {
                        navigator.sendBeacon(
                            CALL_API_URL,
                            new Blob(
                                [JSON.stringify({ action: 'end_call', call_id: currentCallId, reason: 'ended' })],
                                { type: 'application/json' }
                            )
                        );
                    }
                });
            </script>
        <?php else: ?>
            <div class="chat-header">Messages</div>
            <div class="empty-state" style="padding:22px;">Select a contact on the left to start or continue chatting.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'forum_footer.php'; ?>
